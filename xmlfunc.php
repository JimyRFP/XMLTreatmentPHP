<?php
class xmlControl{
  public function getTagIndexName($name,$ind){
    $retname="";
    if(is_array($name)){      
      foreach ($name as $key => $value) {
         $retname.=$value."-";
      }
    }else{
        $retname=$name."-";
    }
    return $retname.strval($ind);
  }

  public function xml2array($xml,$options=[]){
     $default=[
     "ReturnType"=>"InfoArray",
     ];
     foreach ($default as $key => $value) {
     	if(!isset($options[$key]))continue;
     	$default[$key]=$options[$key];
     }
     $this->sendDebugMessage("options=[ReturnType]=".$default["ReturnType"].
                                             "<br>xml=".$xml,
                                             __FUNCTION__
                                             );

     

     $tags=$this->getTags($xml);
     if(!$tags)return false;
     $getValues=$this->getValues($xml,$tags);

     switch ($default["ReturnType"]) {
     	case 'InfoArray':
     		return $getValues;
     		break;
     	case 'ExpandableArray':
     	    return $this->sendToArray($getValues);
     	    break;
     	case 'indexArray':
     	    return $this->getIndexArray($getValues);
     	    break;    
     	default:
     		return $getValues;
     		break;
     }
    
    

  }  
   
  public function getLastErro(){
    return $this->erroreturnmessage[$this->lasterro];
  }
  public function getLastDebugInfo(){
    return $this->debuginfo;
  }

  private function getIndexArray($tags){
  	$ret=[];
  	$size=count($tags["tagname"]);
  	for($i=0;$i<$size;$i++){
       if($i+1<$size)
       if($tags["tagind"][$i+1]>$tags["tagind"][$i])continue;
  	   $ret[$this->getIndexTagName($tags,$i,$ret)]=$tags["tagvalue"][$i];
  	}
    return $ret;
  }

  private function getIndexTagName($tag,$ind,$ret){
    $tagind=$tag["tagind"][$ind];
    $tagname=[];
  	for($i=0;$i<=$tagind;$i++){
       $j=$ind;
       for(;$j>=0;$j--){
         if($tag["tagind"][$j]==$i)break;
       }
     $tagname[]=$tag["tagname"][$j];
  	}
    $i=0;
    $rettagname=$this->getTagIndexName($tagname,$i);
    while(isset($ret[$rettagname])){
        $i++;
    	$rettagname=$this->getTagIndexName($tagname,$i);
    }

  	return $rettagname;

  }
  

  private function sendToArray($tags){
     $ret;
     $size=count($tags["tagname"]);
     for($i=0;$i<$size;$i++){
        if($i+1<$size)
          if($tags["tagind"][$i+1]>$tags["tagind"][$i]){
          	$this->addArrayTag($ret,$tags,$i,false,0);
          	continue;
          }
          $this->addArrayTag($ret,$tags,$i,true,0);
          

     }

   return $ret;
  }

  private function addArrayTag(&$ret,$tags,$ind,$addvalue,$nrep=0){
     if($nrep==$tags["tagind"][$ind]){
       if($addvalue){
         $ret[$tags["tagname"][$ind]]=$tags["tagvalue"][$ind];
         
       }else{
         $ret[$tags["tagname"][$ind]]=[];
        
       }    
     }else{
     	$newind=0;
     	for($i=$ind-1;$i>=0;$i--){
     		if($tags["tagind"][$i]==$nrep){$newind=$i;break;}
     	}
     	$nrep++;

        $this->addArrayTag($ret[$tags["tagname"][$newind]],$tags,$ind,$addvalue,$nrep);

     }

  }

  private function getValues($xml,$tags){
  	$ret=$tags;
  	$ret["tagvalue"]=[];
  	$size=count($ret["tagname"]);
  	$j=0;
  	for($i=0;$i<$size;$i++){
  		if($i+1<$size)
  		if($ret["tagind"][$i]<$ret["tagind"][$i+1]){$ret["tagvalue"][]="";continue;}
  	    $ret["tagvalue"][]=$this->getTagValue($ret["tagname"][$i],$xml,$j);
  	    
  	}
  return $ret;
  }

  private function getTagValue($tagname,$xml,&$i){
  	$value="";
  	$size=strlen($xml);
  	for(;$i<$size;$i++){
      if($xml[$i]!="<")continue;
      $i++;
      $name=$this->GetName($xml,$i);
      if($name!=$tagname)continue;
      $i++; 
      while($i<$size && $xml[$i]!="<"){
        $value.=$xml[$i];
        $i++;
      }
      break;

  	}
   return trim($value);
  }
  
  private function hasNoSpecialChars($data,$number=true){
    for($i=0;$i<strlen($data);$i++){
	$currentAsc2=ord($data[$i]); 
	if(($currentAsc2>47 && $currentAsc2<58 && $number)  ||
	   ($currentAsc2>96 && $currentAsc2<123) ||
	   ($currentAsc2>64 && $currentAsc2<91)
	   ){continue;}
		return false;
    }
     return true;

  }

  public function getTags($xml){
    $ret=["tagname"=>[],"tagind"=>[],"tagisclose"=>[]];
  	$lastind=-1;
  	$size=strlen($xml);
  	$tempname="";
  	for($i=0;$i<$size;$i++){      
      if($xml[$i]=="<"){
        $this->sendDebugMessage("Ind:".strval($i),
                                                __FUNCTION__
                                                );
      	if($i+1==$size)break;
      	if($xml[$i+1]=="/"){ 

      		$i+=2;
      		$tempname=$this->getName($xml,$i);

          $this->sendDebugMessage("End Tag Ind:".strval($i).
                                                  " TagName ".$tempname,
                                                __FUNCTION__
                                                );
      		
      		if(!$this->hasNoSpecialChars($tempname,true))
          { 
            $this->debuginfo="Tag end".$tempname;
            $this->lasterro="endtagwithspecialchar";
            return false;
          }

      		if($tempname!=$this->getLastDontCloseInd($ret,"tagname"))
          {
            $this->debuginfo="Last dont close tag: ".$this->getLastDontCloseInd($ret,"tagname").
            " Tag end: ".$tempname;
            $this->lasterro="diferentdontlastclosetag";
            return false;
          }
      		$this->getLastDontCloseInd($ret,"tagname",true);      		
      		$lastind=$this->getLastDontCloseInd($ret,"tagind");
          $this->sendDebugMessage("End Tag Ind:".strval($i).
                                                  " TagName ".$tempname.
                                                  " LastInd:".strval($lastind),
                                                __FUNCTION__
                                                );


      	}else{
      	    $i++;
      	    $tempname=$this->getName($xml,$i);
            $this->sendDebugMessage("Open Tag Ind:".strval($i).
                                    " TagName ".$tempname,
                                    __FUNCTION__
                                                );
      	    
      	    if(!$this->hasNoSpecialChars($tempname,true)){continue;}
            $ret["tagname"][]=$tempname;
            $ret["tagind"][]=$lastind>-1?$lastind+1:0;
            $ret["tagisclose"][]=false;
            $lastind++;    
             

      	} //IF /
      } // IF <

     
  	} //FOR
    return $ret;



  }

  private function getName($val,&$ind){
  	$ret="";
  	for(;$ind<strlen($val);$ind++){
     if($val[$ind]==">"){break;}
  	 $ret.=$val[$ind];
  	}
    return trim($ret);
  }

  private function getLastDontCloseInd(&$array,$info,$close=false){
  	$dontclose=-1;
    
    for($i=count($array["tagisclose"])-1;$i>=0;$i--){
    	if(!$array["tagisclose"][$i]){$dontclose=$i;break;}
    }
    if($dontclose<0){
      $this->sendDebugMessage("Return -1",__FUNCTION__);
      return -1;
    }
    if($close){
       $array["tagisclose"][$dontclose]=true;
       $this->sendDebugMessage("Close Tag: ".$array[$info][$dontclose]." Ind: ".$dontclose,__FUNCTION__);
    }  
    $this->sendDebugMessage("Return ".$info." TagValue: ".$array[$info][$dontclose],__FUNCTION__);
    return $array[$info][$dontclose];
  
  }
  
  private $erroreturnmessage=["noerro"=>"No Error",
                              "endtagwithspecialchar"=>"End Tag With Special Char.",
                              "diferentdontlastclosetag"=>"Invalid end of tag."
                             ];
  private $lasterro="noerro";
  private $debuginfo="";
  private function sendDebugMessage($message,$funcname){
    if(!$this->debug)return;
    echo "<br>__DEBUG__ function:".$funcname." message:".$message."<br>";
  }
  public $debug=false;





};




?>