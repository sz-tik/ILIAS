<?php

include_once "classes/class.Object.php";

/**
 * Class RbacAdmin 
 * core functions for role based access control
 * @author Stefan Meyer <smeyer@databay.de>
 * @version $Id$
 * @package rbac
 */
class RbacAdmin extends PEAR
{
    var $db;			//  Database Handle

	var $error_class;

/**
 * Constructor 
 * @access public
 * 
 */
    function RbacAdmin(&$dbhandle)
    {
		$this->PEAR();
		$this->error_class = new ErrorHandling();
		$this->setErrorHandling(PEAR_ERROR_CALLBACK,array($this->error_class,'errorHandler'));

        $this->db =& $dbhandle;
    }
/**
 * Checks if a role exists
 * @access public
 * @param string
 * @return array 
 */
    function roleExists($a_title)
    {
		$res = $this->db->query("SELECT obj_id FROM object_data ".
								"WHERE title ='".$a_title.
								"' AND type = 'role'");
		while($res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$id[] = $row->obj_id;
		}
		return count($id);
    }
/**
 * Inserts userdata in user_data table
 * @access public
 * @param  array 
 * @return bool true/false
 */
	function addUser($a_data)
    {

		$passwd = md5($a_data["Passwd"]);
		$query = "INSERT INTO user_data ".
			"(usr_id,login,passwd,firstname,surname,title,gender,email,last_login,last_update,create_date) ".
			"VALUES('".$a_data["Id"]."','".$a_data["Login"]."','".$passwd."','".$a_data["FirstName"].
			"','".$a_data["SurName"]."','".$a_data["Title"]."','".$a_data["Gender"]."','".$a_data["Email"].
			"',0,now(),now())";

		$res = $this->db->query($query);
		return true;
    }
/**
 * Deletes a user from object_data, rbac_pa, rbac_ua and user_data
 * @access public
 * @param array
 * @return boolean true/false
 */
    function deleteUser($a_usr_id)
    {
		foreach($a_usr_id as $id)
		{
			// Eintr�ge in object_data
			$res = $this->db->query("DELETE FROM object_data ".
									"WHERE obj_id='".$id."'");
			$res = $this->db->query("DELETE FROM rbac_pa ".
									"WHERE obj_id='".$id."'");
			$res = $this->db->query("DELETE FROM rbac_ua ".
									"WHERE usr_id='".$id."'");
			$res = $this->db->query("DELETE FROM user_data ".
									"WHERE usr_id='".$id."'");
		}
		return true;
    }
/**
 * updates user data in table user_data
 * @access public
 * @param array Array with user data
 * @return bool true false
 */
	function updateUser($a_userdata)
	{
		$query = "UPDATE user_data ".
			"SET ".
			"login = '".$a_userdata["Login"]."',".
//			"passwd = '".$a_userdata["Passwd"]."',".
			"firstname = '".$a_userdata["FirstName"]."',".
			"surname = '".$a_userdata["SurName"]."',".
			"title = '".$a_userdata["Title"]."',".
			"gender = '".$a_userdata["Gender"]."',".
			"email = '".$a_userdata["Email"]."'".
			" WHERE usr_id = '".$a_userdata["Id"]."'";
		$res = $this->db->query($query);
		return true;
	}
/**
 * Creates a role, inserts data in object_data, rbac_ua, rbac_pa
 * @access public
 * @param string title
 * @param string description
 * @return  int new obj_id
 */
    function addRole($a_title,$a_description)
    {
		$rbacreview = new RbacReview($this->db);

		if($this->roleExists($a_title))
		{
			return $this->raiseError("Role Title already exists",$this->error_class->WARNING);
		}
		// Anlegen der Rolle in object_data
		$query = "INSERT INTO object_data (type,title,description,owner,create_date,last_update) ".
			"VALUES ('role','".$a_title."','".$a_description ."','-1',now(),now())";

		$res = $this->db->query($query);

		// Eintrag in rbac_ua
 		$query = "SELECT LAST_INSERT_ID()";
		$res = $this->db->query($query);
		$row = $res->fetchRow();
		if(!$this->assignUser($row[0]))
		{
			return $this->raiseError($res->getMessage().": ".$res->getDebugInfo(),$this->error_class->WARNING);
		}

		// Eintrag der Permissions in rbac_pa
		$rolops = $rbacreview->getRolesOperations("role");
		foreach($rolops as $r)
		{
			// TODO: set_id muss den Wert des aktuellen Container
			// erhalten, in dem die Rolle angelegt wurde
			$this->grantPermission($r["rol_id"],$r["ops_id"],$row[0],1);
		}
		return $row[0];
	}
	/**
	 * Deletes a role and deletes entries in object_data, rbac_pa, rbac_templates, rbac_ua, rbac_fa
	 * @access public
	 * @param int Object Id
	 * @return bool true/false
	 */
    function deleteRole($a_obj_id)
    {
		$this->db->query("DELETE FROM object_data ".
						 "WHERE obj_id = '".$a_obj_id ."'");
		$this->db->query("DELETE FROM rbac_pa ".
						 "WHERE rol_id = '".$a_obj_id ."'");
		$this->db->query("DELETE FROM rbac_templates ".
						 "WHERE rol_id = '".$a_obj_id ."'");
		$this->db->query("DELETE FROM rbac_ua ".
						 "WHERE rol_id = '".$a_obj_id ."'");
		$this->db->query("DELETE FROM rbac_fa ".
						 "WHERE rol_id = '".$a_obj_id ."'");
		return 1;
    }
/**
 * Deletes a local role and entries in rbac_fa and rbac_templates
 * @access public
 * @param int Object Id of role
 * @param int Obkect of parent object
 * @return bool true/false
 */
	function deleteLocalRole($a_rol_id,$a_parent)
	{
		$query = "DELETE FROM rbac_fa ".
			"WHERE rol_id = '".$a_rol_id."' ".
			"AND parent = '".$a_parent."'";
		$res = $this->db->query($query);
		$query = "DELETE FROM rbac_templates ".
			"WHERE rol_id = '".$a_rol_id."' ".
			"AND parent = '".$a_parent."'";
		$res = $this->db->query($query);
		return true;
	}
/**
 * Get parent roles in a path
 * @access public
 * @param array Path Id
 * @return bool true/false
 */
	function getParentRoles($a_path,$a_child = "")
	{
		$parentRoles = array();

		if(!$a_child)
		{
			$a_child = $this->getRoleFolder();
		}
		// CREATE IN() STATEMENT
		$in = " IN('";
		if(count($a_child) > 1)
		{
			$in .= implode("','",$a_child);
		}
		else
		{
			$in .= $a_child[0];
		}
		$in .= "')";
		foreach($a_path as $path)
		{
			$query = "SELECT * FROM tree ".
				" WHERE child ".$in.
				" AND parent = '".$path."'";
			$res = $this->db->query($query);
			if(DB::isError($res))
			{
				return $this->raiseError($res->getMessage().": ".$res->getDebugInfo(),$this->error_class->FATAL);
			}
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$roles = $this->getRoleListByObject($row->child);
				foreach($roles as $role)
				{
					$id = $role["obj_id"];
					$role["parent"] = $row->child;
					$parentRoles[$id] = $role;
				}
			}
		}
		return $parentRoles;
	}
/**
 * Assigns a user to a role
 * @access public
 * @param int object id of role
 * @param int object id of user
 * @return bool true/false
 */
    function assignUser($a_rol_id,$a_usr_id = 0)
    {
        // Zuweisung des aktuellen Benutzers zu der Rolle
		if(!$a_usr_id)
		{
			global $ilias;
			$a_usr_id = $ilias->account->data["Id"];
		}
		$query = "INSERT INTO rbac_ua ".
			"VALUES ('".$a_usr_id."','".$a_rol_id."')";
		$res = $this->db->query($query);
		return 1;
    }
/**
 * Deassigns a user from a role
 * @access public
 * @param int object id of role
 * @param int user id
 * @return bool true/false
 */
    function deassignUser($a_rol_id,$a_usr_id)
    {
		$query = "DELETE FROM rbac_ua ".
			"WHERE usr_id='".$a_usr_id."' ".
			"AND rol_id='".$a_rol_id."'";
		$res = $this->db->query($query);
		return 1;
    }
/**
 * Grants permissions to an object
 * @access public
 * @param int object id of role
 * @param array array of operations
 * @param int object id
 * @param int obj id of parent object
 * @return bool true/false
 */
    function grantPermission($a_rol_id,$a_ops_id,$a_obj_id,$a_setid)
    {
		// Serialization des ops_id Arrays
		$ops_ids = addslashes(serialize($a_ops_id));
		$query = "INSERT INTO rbac_pa VALUES('".$a_rol_id."','".$ops_ids."','".$a_obj_id."','".$a_setid."')";
		$res = $this->db->query($query);
		return 1;
    }
/**
 * Revokes permissions of object
 * @access public
 * @param int object id
 * @param int role id
 * @param int id of parent object
 * @return bool true/false
 */
    function revokePermission($a_obj_id,$a_rol_id = "",$a_set_id = "")
    {
		if($a_set_id)
			$and1 = " AND set_id = '".$a_set_id."'";
		else
			$and1 = "";

		if($a_rol_id)
			$and2 = " AND rol_id = '".$a_rol_id."'";
		else
			$and2 = "";

		$query = "DELETE FROM rbac_pa ".
			"WHERE obj_id = '".$a_obj_id."' ".
			$and1.
			$and2;
		$res = $this->db->query($query);
		return 1;
    }
/**
 * Return template permissions of an role
 * @access public
 * @param int role id
 * @param string
 * @param int
 * @return array Operation ids
 */
    function getRolePermission($a_rol_id,$a_type,$a_parent)
    {
		$ops_id = array();
		$query = "SELECT ops_id FROM rbac_templates ".
			"WHERE rol_id='".$a_rol_id."' ".
			"AND type='".$a_type."' ".
			"AND parent='".$a_parent."'";
		
		$res = $this->db->query($query);
		if(!$res->numRows())
			return $ops_id;
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops_id[] = $row->ops_id;
		}
		return $ops_id;
    }
/**
 * Copies template permissions
 * @access public
 * @param int role id source
 * @param int parent id source
 * @param int role id destination
 * @param int parent id destination
 * @return bool 
 */
	function copyRolePermission($a_rol_id,$a_from,$a_to,$a_dest_rol_id = '')
	{
		$a_dest_rol_id = $a_dest_rol_id ? $a_dest_rol_id : $a_rol_id;
		$query = "SELECT * FROM rbac_templates ".
			"WHERE rol_id = '".$a_rol_id."' ".
			"AND parent = '".$a_from."'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$query = "INSERT INTO rbac_templates ".
				"VALUES('".$a_dest_rol_id."','".$row->type."','".$row->ops_id."','".$a_to."')";
			$result = $this->db->query($query);
		}
		return true;
	}
/**
 * Deletes a template
 * @access public
 * @param int role id
 * @param int parent object id
 * @return bool
 */
	function deleteRolePermission($a_rol_id,$a_parent)
	{
		$query = "DELETE FROM rbac_templates ".
			"WHERE rol_id = '".$a_rol_id."' ".
			"AND parent = '".$a_parent."'";
		$res = $this->db->query($query);
		return true;
	}
/**
 * Inserts template permissions in rbac_templates
 * @access public
 * @param int rol id
 * @param string
 * @param array operation ids
 * @param parent object id
 * @return bool
 */
    function setRolePermission($a_rol_id,$a_type,$a_ops_id,$a_parent)
    {
		if(!$a_ops_id)
			$a_ops_id = array();

		foreach($a_ops_id as $o)
		{
			$query = "INSERT INTO rbac_templates ".
				"VALUES('".$a_rol_id."','".$a_type."','".$o."','".$a_parent."')";
			$res = $this->db->query($query);
		}
		return 1;
    }
/**
 * Returns parent id of an object (obsolete)
 * @access public
 * @param int object id 
 * @return array parent ids
 */
    function getSetIdByObject($a_obj_id)
    {
		$set_id = array();
		$query = "SELECT DISTINCT set_id FROM rbac_pa ".
			"WHERE obj_id = '".$a_obj_id."'";
		$res = $this->db->query($query);
        while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$set_id[] = $row->set_id;
        }
		return $set_id;
    }
/**
 * Returns a list of roles in an container
 * @access public
 * @param int object id
 * @return array set ids
 */
	function getRoleListByObject($a_parent,$a_order='',$a_direction='')
	{
		$role_list = array();

		if(!$a_order)
			$a_order = 'type';

		$query = "SELECT * FROM object_data ".
			"JOIN rbac_fa ".
			"WHERE object_data.type = 'role' ".
			"AND object_data.obj_id = rbac_fa.rol_id ".
			"AND rbac_fa.parent = '".$a_parent."' ".
			"ORDER BY ".$a_order." ".$a_direction;
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$role_list[] = array( 
				"obj_id"            => $row->obj_id,
				"type"              => $row->type,
				"title"             => $row->title,
				"description"       => $row->description,
				"owner"             => $row->owner,
				"create_date"       => $row->create_date,
				"last_update"       => $row->last_update);
		}
		return $role_list;
	}
/**
 * Assigns a role to an role folder
 * @access public
 * @param int object id of role
 * @param parent object id
 * @param string asignable('y','n')
 * @return bool
 */
	function assignRoleToFolder($a_rol_id,$a_parent,$a_assign = 'y')
	{
		$query = "INSERT INTO rbac_fa (rol_id,parent,assign) ".
			"VALUES ('".$a_rol_id."','".$a_parent."','".$a_assign."')";
		$res = $this->db->query($query);
		return true;
	}
/**
 * Check if its possible to assign users
 * @access public
 * @param int object id
 * @param int parent id
 * @return bool 
 */
	function isAssignable($a_rol_id,$a_parent)
	{
		$query = "SELECT * FROM rbac_fa ".
			"WHERE rol_id = '".$a_rol_id."' ".
			"AND parent = '".$a_parent."'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->assign == 'y' ? true : false;
		}
	}
/**
 * gets data of an role
 * @access public
 * @param int object id  
 * @return array array of set ids
 */
	function getRoleData($a_obj_id)
	{
		$role_list = array();

		$query = "SELECT * FROM object_data ".
			"WHERE type = 'role' ".
			"AND obj_id = '".$a_obj_id."'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$role_list = array( 
				"obj_id"            => $row->obj_id,
				"type"              => $row->type,
				"title"             => $row->title,
				"description"       => $row->description,
				"owner"             => $row->owner,
				"create_date"       => $row->create_date,
				"last_update"       => $row->last_update);
		}
		return $role_list;
	}
/**
 * returns an array with role ids assigned to a role folder
 * @access public
 * @param int role id
 * @return array object ids of role folders
 */
	function getFoldersAssignedToRole($a_rol_id)
	{
		$parent = array();
		
		$query = "SELECT DISTINCT parent FROM rbac_fa ".
			"WHERE rol_id = '".$a_rol_id."'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$parent[] = $row->parent;
		}
		return $parent;
	}
/**
 * return an array with role ids
 * @access public
 * @param int parent id  
 * @return array Array with rol_ids
 */
	function getRolesAssignedToFolder($a_parent)
	{
		$query = "SELECT rol_id FROM rbac_fa ".
			"WHERE parent = '".$a_parent."'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$rol_id[] = $row->rol_id;
		}
		return $rol_id ? $rol_id : array();
	}
/**
 * all role folder ids
 * @access public
 * @param int object id of role folder  
 * @return array
 */
	function getRoleFolder()
	{
		$parent = array();
		
		$query = "SELECT DISTINCT parent FROM rbac_fa";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$parent[] = $row->parent;
		}
		return $parent;
	}
/**
 * returns the data of a role folder assigned to an object
 * @access public
 * @param int parent id
 * @return array
 */
	function getRoleFolderOfObject($a_parent)
	{
		$rol_data = array();

		$query = "SELECT * FROM tree ".
			"LEFT JOIN object_data ON tree.child=object_data.obj_id ".
			"WHERE parent = '".$a_parent."' ".
			"AND type = 'rolf'";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$rol_data["child"] = $row->obj_id;
			$rol_data["parent"] = $row->parent;
		}
		return $rol_data;
	}
/**
 * return id of parent object
 * @access public
 * @param int  
 * @return int
 */
	function getParentObject($a_child)
	{
		$res = $this->db->query("SELECT * FROM tree ".
								"WHERE child = '".$a_child."'");
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$parent = $row->parent;
		}
		return $parent;
	}
/**
 * all possible operations of a type
 * @access public
 * @param int 
 * @return array
 */
	function getOperationsOnType($a_typ_id)
	{
		$res = $this->db->query("SELECT * FROM rbac_ta ".
								"WHERE typ_id = '".$a_typ_id."'");
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$ops_id[] = $row->ops_id;
		}
		return $ops_id ? $ops_id : array();
	}
}
?>