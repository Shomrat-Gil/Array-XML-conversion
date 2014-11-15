<?php

/**
*  This class used to convert  XML in to array and to convert  array into  XML    
 *
 * 
 * @category XML
 * @package  XML
 * @author   Gil Shomrat
 * 
 * 
 * 
*/
class xmlManagement {          
                            
    public static $arrError = array(); //use for `error` response from othere classes
    public static $arrMessage = array(); //use for `messages` response from othere classes
    public static $arrData = array();//use for `data` response from othere classes
    public static $arrOperation = array();//use for `operation` status + state response from othere classes
    public static $arrElement = array(); 
  //   public static $arrToken = array();//use for `Token` response from othere classes
  
  

    
      /**
      * XML to Array Conversion
      * 
      * @param mixed $strXml = XML string
      */
      public static function getXML($strXml=null){
             if(empty($strXml)){return false;}  
             xmlManagement::$arrError = array();
             xmlManagement::$arrMessage = array();
             xmlManagement::$arrData = array();
             xmlManagement::$arrOperation = array();
             $xml = simplexml_load_string($strXml,'SimpleXMLElement', LIBXML_NOCDATA);
             return xmlManagement::ParseXML($xml );
      }
      
      /**
      * XML parser to array - recursive function
      * 
      * This function parses the XML string, handed as a parameter, and returns a nested array of the values
      *   
      * @param mixed $node
      * @param mixed $arrParent
      * @param mixed $onlyChild
      */             
     public static function ParseXML($xml) {  
            // if it a single node and it has attributes convert that node to child key 0
            if(!$xml->count() && $xml->attributes()){
                //$val = validations::validate((string)$xml,"string");
                $val = filter_var((string)$xml, FILTER_SANITIZE_STRING,FILTER_FLAG_NO_ENCODE_QUOTES);
                $val = trim($val);
                $val = nl2br($val,false); 
                $xml->addChild(0, $val); 
            }
            //Get attributes of current node and force them as childs
            foreach ($xml->attributes() as $key=>$val) {
                $key = validations::validate($key,"field");
                //$val = validations::validate((string)$val,"string");
                $val = filter_var((string)$val, FILTER_SANITIZE_STRING,FILTER_FLAG_NO_ENCODE_QUOTES);
                $xml->addChild($key, trim($val)); 
            }
            // if no child       
            if (!($xml->children())) { 
                //return validations::validate((string)$xml,"string");  
                //$val = filter_var((string)$xml, FILTER_SANITIZE_STRING,FILTER_FLAG_NO_ENCODE_QUOTES);                
                $val = validations::validate( (string)$xml ,"string_html");
                $val = trim($val);
                $val = nl2br($val,false); 
                return $val;
                //return  filter_var((string)$xml, FILTER_SANITIZE_STRING,FILTER_FLAG_NO_ENCODE_QUOTES);   
            }  
            // loop over children 
            foreach ($xml->children() as $child) { 
                $name=$child->getName(); 
                $name = validations::validate($name,"field"); 
                // if single node or node with attributes
                if (count($xml->$name)==1 || (!$xml->count() && $xml->attributes() ) ) {   
                    $element[$name] = xmlManagement::ParseXML($child); 
                    //$element[$name] = xmlManagement::attributesToArray($child,$element[$name]);
                } else { 
                    $element[][$name] = xmlManagement::ParseXML($child); 
                   // $element[$loop][$name] = xmlManagement::attributesToArray($child,$element[$loop][$name]);
                } 
            } 

            return $element;   
        }
    
 
   
     /**
    * Array to XML Conversion - recursive function
    *  
    * @param mixed $arrData
    */   
    public static function generate_xml_element(  $array, $xml = null)  {
        
        $arrAttributes = array('DisplayOrderId'=>true,'Id'=>true,'userId'=>true,'level'=>true,'code'=>true); 
         
          if ($xml === null) {
           /* $xml = new SimpleXMLElement( "<?xml version=\"1.0\" encoding=\"utf-8\" ?><response></response>");  */
            $xml = new SimpleXMLExtended( "<?xml version=\"1.0\" encoding=\"utf-8\" ?><response></response>");
             
          }
         
          foreach ($array as $k => $v) {
            if (is_array($v)) { //nested array                   
                if(is_numeric($k)){
                    // if its a array key
                    $key = key($v);
                    if(!isset($key)){continue;}
                    // check and create attributes
                    foreach($arrAttributes as $strAttribute=>$arrAttribute){
                         if(isset($v[$strAttribute])){
                               $strText = xmlManagement::cleanUpHTML($v[$strAttribute]) ;
                               $xml->addAttribute($strAttribute, $strText);
                               unset($v[$strAttribute]);
                         }
                    }      
                    xmlManagement::generate_xml_element($v,  $xml ); 
                } else{  
                    $arrChildAttributes = array();
                    // check if children are attributed
                    $arrVals = $v;
                    $intSetAttribute = false;
                    foreach($arrAttributes as $strAttribute=>$arrAttribute){
                        if(isset($arrVals[$strAttribute])){
                            $arrChildAttributes[$strAttribute] = $arrVals[$strAttribute];
                            unset($arrVals[$strAttribute]);
                            $intSetAttribute = true;
                        }
                    }   
                    // if one array value and its key is number  
                    if(count($arrVals)==1 && is_numeric( key($arrVals) ) && $intSetAttribute===true ){
                        $val = current($arrVals);
                       // $newNode = $xml->addChild($k, trim( (string)$val ));
                        
                       $val = trim( (string)$val ) ;
                       // if the string is numeric or empty
                       if(is_numeric($val) || empty($val)){
                            $newNode = $xml->addChild($k, $val);
                       }else{
                           // add the string as cdata
                            $newNode = $xml->addChild($k); 
                            $newNode->addCData($val); 
                       } 
                        
                        foreach($arrChildAttributes as $strAttribute=>$val){
                            $strText = xmlManagement::cleanUpHTML($val) ;
                            $newNode->addAttribute($strAttribute, $strText);
                        }
                    } else{
                       xmlManagement::generate_xml_element($v,  $xml->addChild($k) );  
                    } 
                     
                }   
               // xmlManagement::generate_xml_element($v, $k, $xml->addChild($k) );
            }  else{
                if(empty($arrAttributes[$k])){
                    // add child 
                       $strText = xmlManagement::cleanUpHTML($v) ;
                       // if the string is numeric or empty
                       if(is_numeric($strText) || empty($strText)){
                            $xml->addChild($k, $strText);
                       }else{
                           // add the string as cdata
                            $node_note = $xml->addChild($k); 
                            $node_note->addCData($strText); 
                       }   
                } else{
                    // if defined as attribute
                    $strText = xmlManagement::cleanUpHTML($v) ;
                    $xml->addAttribute($k, $strText);  
                }  
            }
          }
 
  return $xml->asXML();
}

    
  
 
  
    /**
    * Clean text as string 
    * And
    * Clean Up Microsoft Word HTML Special Characters
    * 
    * @param mixed $strText
    */
    private static function cleanUpHTML($strText = null){
        if( !empty($strText)){
             $strText = trim( (string)$strText );  
        } 
        return $strText;
    }
 
 // END CLASS
}

//write CDATA using SimpleXmlElement
class SimpleXMLExtended extends SimpleXMLElement{ 
    /** 
    * add child with cdata
    * 
    * @param mixed $cdata_text
    */
      public function addCData($cdata_text){ 
           $node= dom_import_simplexml($this); 
           $no = $node->ownerDocument; 
           $node->appendChild($no->createCDATASection($cdata_text)); 
      } 
// END CLASS        
} 
 