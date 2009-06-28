<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MyDomDocument
 *
 * @author Altairm
 */
class MyDomDocument extends DOMDocument{
    public function saveArray($node = null) {
        if(empty($node)) {
            $node = $this;
        }
        $res = array();

        if($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if(XML_TEXT_NODE == $child->nodeType) {
                    $res['value'] = $child->nodeValue;
                } else {
//                    if(isset($res[$child->nodeName])) {
//                        $tmp = $res[$child->nodeName];
//                        $res[$child->nodeName] = array($tmp);
//                        $res[$child->nodeName][] = $this->saveArray($child);
//                    } else {
//                        $res[$child->nodeName] = $this->saveArray($child);
//                    }
                    $res[$child->nodeName][] = $this->saveArray($child);
                }
            }
        }
        if($node->hasAttributes()) {
            foreach($node->attributes as $attr) {
                $res['attributes'][$attr->nodeName] = $attr->nodeValue;
            }
        }
        return $res;
    }

}
?>
