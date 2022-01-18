<?php
/*
    +-----------------------------------------------------------------------------+
    | ILIAS open source                                                           |
    +-----------------------------------------------------------------------------+
    | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
    |                                                                             |
    | This program is free software; you can redistribute it and/or               |
    | modify it under the terms of the GNU General Public License                 |
    | as published by the Free Software Foundation; either version 2              |
    | of the License, or (at your option) any later version.                      |
    |                                                                             |
    | This program is distributed in the hope that it will be useful,             |
    | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
    | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
    | GNU General Public License for more details.                                |
    |                                                                             |
    | You should have received a copy of the GNU General Public License           |
    | along with this program; if not, write to the Free Software                 |
    | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
    +-----------------------------------------------------------------------------+
*/


/**
* Meta Data class (element contribute)
*
* @author Stefan Meyer <meyer@leifos.com>
* @package ilias-core
* @version $Id$
*/
include_once 'class.ilMDBase.php';

class ilMDContribute extends ilMDBase
{
    // Subelements
    private string $date = '';
    private string $role = '';

    /**
     * @return int[]
     */
    public function getEntityIds() : array
    {
        include_once 'Services/MetaData/classes/class.ilMDEntity.php';

        return ilMDEntity::_getIds($this->getRBACId(), $this->getObjId(), $this->getMetaId(), 'meta_contribute');
    }
    public function getEntity(int $a_entity_id) : ?ilMDEntity
    {
        include_once 'Services/MetaData/classes/class.ilMDEntity.php';
        
        if (!$a_entity_id) {
            return null;
        }
        $ent = new ilMDEntity();
        $ent->setMetaId($a_entity_id);

        return $ent;
    }
    public function addEntity() : ilMDEntity
    {
        include_once 'Services/MetaData/classes/class.ilMDEntity.php';

        $ent = new ilMDEntity($this->getRBACId(), $this->getObjId(), $this->getObjType());
        $ent->setParentId($this->getMetaId());
        $ent->setParentType('meta_contribute');

        return $ent;
    }

    // SET/GET
    public function setRole(string $a_role) : bool
    {
        switch ($a_role) {
            case 'Author':
            case 'Publisher':
            case 'Unknown':
            case 'Initiator':
            case 'Terminator':
            case 'Editor':
            case 'GraphicalDesigner':
            case 'TechnicalImplementer':
            case 'ContentProvider':
            case 'TechnicalValidator':
            case 'EducationalValidator':
            case 'ScriptWriter':
            case 'InstructionalDesigner':
            case 'SubjectMatterExpert':
            case 'Creator':
            case 'Validator':
            case 'PointOfContact':
                $this->role = $a_role;
                return true;

            default:
                return false;
        }
    }
    public function getRole() : string
    {
        return $this->role;
    }
    public function setDate(string $a_date) : void
    {
        $this->date = $a_date;
    }
    public function getDate() : string
    {
        return $this->date;
    }


    public function save() : bool
    {

        $fields = $this->__getFields();
        $fields['meta_contribute_id'] = array('integer',$next_id = $this->db->nextId('il_meta_contribute'));
        
        if ($this->db->insert('il_meta_contribute', $fields)) {
            $this->setMetaId($next_id);
            return $this->getMetaId();
        }
        return false;
    }

    public function update() : bool
    {
        
        if ($this->getMetaId()) {
            if ($this->db->update(
                'il_meta_contribute',
                $this->__getFields(),
                array("meta_contribute_id" => array('integer',$this->getMetaId()))
            )) {
                return true;
            }
        }
        return false;
    }

    public function delete() : bool
    {
        
        if ($this->getMetaId()) {
            $query = "DELETE FROM il_meta_contribute " .
                "WHERE meta_contribute_id = " . $this->db->quote($this->getMetaId(), 'integer');
            $res = $this->db->manipulate($query);
            
            foreach ($this->getEntityIds() as $id) {
                $ent = $this->getEntity($id);
                $ent->delete();
            }
            return true;
        }
        return false;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function __getFields() : array
    {
        return array('rbac_id' => array('integer',$this->getRBACId()),
                     'obj_id' => array('integer',$this->getObjId()),
                     'obj_type' => array('text',$this->getObjType()),
                     'parent_type' => array('text',$this->getParentType()),
                     'parent_id' => array('integer',$this->getParentId()),
                     'role' => array('text',$this->getRole()),
                     'c_date' => array('text',$this->getDate()));
    }

    public function read() : bool
    {
        
        include_once 'Services/MetaData/classes/class.ilMDLanguageItem.php';

        if ($this->getMetaId()) {
            $query = "SELECT * FROM il_meta_contribute " .
                "WHERE meta_contribute_id = " . $this->db->quote($this->getMetaId(), 'integer');

            $res = $this->db->query($query);
            while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
                $this->setRBACId($row->rbac_id);
                $this->setObjId($row->obj_id);
                $this->setObjType($row->obj_type);
                $this->setParentId($row->parent_id);
                $this->setParentType($row->parent_type);
                $this->setRole($row->role);
                $this->setDate((string) $row->c_date);
            }
        }
        return true;
    }
                

    public function toXML(ilXmlWriter $writer) : void
    {
        $writer->xmlStartTag('Contribute', array('Role' => $this->getRole()
                                                ? $this->getRole()
                                                : 'Author'));

        // Entities
        $entities = $this->getEntityIds();
        foreach ($entities as $id) {
            $ent = $this->getEntity($id);
            $ent->toXML($writer);
        }
        if (!count($entities)) {
            include_once 'Services/MetaData/classes/class.ilMDEntity.php';
            $ent = new ilMDEntity($this->getRBACId(), $this->getObjId());
            $ent->toXML($writer);
        }
            
        $writer->xmlElement('Date', null, $this->getDate());
        $writer->xmlEndTag('Contribute');
    }


    // STATIC

    /**
     * @return int[]
     */
    public static function _getIds(int $a_rbac_id, int $a_obj_id, int $a_parent_id, string $a_parent_type) : array
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = "SELECT meta_contribute_id FROM il_meta_contribute " .
            "WHERE rbac_id = " . $ilDB->quote($a_rbac_id, 'integer') . " " .
            "AND obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " " .
            "AND parent_id = " . $ilDB->quote($a_parent_id, 'integer') . " " .
            "AND parent_type = " . $ilDB->quote($a_parent_type, 'text');

        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $ids[] = $row->meta_contribute_id;
        }
        return $ids ? $ids : array();
    }

    /**
     * @return string[]
     */
    public static function _lookupAuthors(int $a_rbac_id, int $a_obj_id, string $a_obj_type) : array
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        // Ask for 'author' later to use indexes
        $query = "SELECT entity,ent.parent_type,role FROM il_meta_entity ent " .
            "JOIN il_meta_contribute con ON ent.parent_id = con.meta_contribute_id " .
            "WHERE  ent.rbac_id = " . $ilDB->quote($a_rbac_id, 'integer') . " " .
            "AND ent.obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " ";
        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            if ($row->role == 'Author' and $row->parent_type == 'meta_contribute') {
                $authors[] = trim($row->entity);
            }
        }
        return $authors ? $authors : array();
    }
}
