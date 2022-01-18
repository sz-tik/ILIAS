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
* Base class for copying meta data from xml
* It is possible to overwrite single elements. See handling of identifier tag
*
* @package ilias-core
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
*/

include_once 'Services/MetaData/classes/class.ilMDSaxParser.php';
include_once 'Services/MetaData/classes/class.ilMD.php';

class ilMDXMLCopier extends ilMDSaxParser
{
    private array $filter = [];
    private bool $in_meta_data = false;

    public function __construct($content, $a_rbac_id, $a_obj_id, $a_obj_type)
    {
        $this->setMDObject(new ilMD($a_rbac_id, $a_obj_id, $a_obj_type));

        parent::__construct();
        $this->setXMLContent($content);

        // set filter of tags which are handled in this class
        $this->__setFilter();
    }
    /**
     * @param	resource $a_xml_parser reference to the xml parser
     */
    public function setHandlers($a_xml_parser)
    {
        xml_set_object($a_xml_parser, $this);
        xml_set_element_handler($a_xml_parser, 'handlerBeginTag', 'handlerEndTag');
        xml_set_character_data_handler($a_xml_parser, 'handlerCharacterData');
    }

    /**
     * @param	resource $a_xml_parser reference to the xml parser
     */
    public function handlerBeginTag($a_xml_parser, string $a_name, array $a_attribs) : void
    {
        if ($this->in_meta_data and !$this->__inFilter($a_name)) {
            parent::handlerBeginTag($a_xml_parser, $a_name, $a_attribs);
            return;
        }
            

        switch ($a_name) {
            case 'MetaData':
                $this->in_meta_data = true;
                parent::handlerBeginTag($a_xml_parser, $a_name, $a_attribs);
                break;

            case 'Identifier':
                $par = $this->__getParent();
                $this->md_ide = $par->addIdentifier();
                $this->md_ide->setCatalog($a_attribs['Catalog']);
                $this->md_ide->setEntry('il__' . $this->md->getObjType() . '_' . $this->md->getObjId());
                $this->md_ide->save();
                $this->__pushParent($this->md_ide);
                break;
        }
    }
    /**
     * @param	resource $a_xml_parser reference to the xml parser
     */
    public function handlerEndTag($a_xml_parser, string $a_name) : void
    {
        if ($this->in_meta_data and !$this->__inFilter($a_name)) {
            parent::handlerEndTag($a_xml_parser, $a_name);
            return;
        }
        switch ($a_name) {
            case 'Identifier':
                $par = $this->__getParent();
                $par->update();
                $this->__popParent();
                break;
                

            case 'MetaData':
                $this->in_meta_data = false;
                parent::handlerEndTag($a_xml_parser, $a_name);
                break;
        }
    }

    /**
     * @param	resource $a_xml_parser reference to the xml parser
     */
    public function handlerCharacterData($a_xml_parser, string $a_data) : void
    {
        if ($this->in_meta_data) {
            parent::handlerCharacterData($a_xml_parser, $a_data);
        }
    }

    public function __setFilter() : void
    {
        $this->filter[] = 'Identifier';
    }

    public function __inFilter(string $a_tag_name) : bool
    {
        return in_array($a_tag_name, $this->filter);
    }
}
