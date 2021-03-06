<?php	
	if (!$_SESSION['mgrRole']) return;
	
	if(!is_array($modx->event->params)){
		$modx->event->params = array();
	}	
	
	include_once(dirname(__FILE__).'/classes/easyCollection.class.php');
	$ec = new easyCollection($modx);	
	
	if ($modx->event->name=='OnManagerNodePrerender'){		
		$hideIds = $ec->findHideChildren();		
		if (count($hideIds)) if (in_array($ph['id'], $hideIds)) {			
			$ph['showChildren'] = false;						
		}
		$modx->event->setOutput(serialize($ph));
	}
	
	
	if (($modx->event->name=='OnManagerPageInit') and (($modx->event->params['action']==3) or ($modx->event->params['action']==27) )){
		if ($_REQUEST['id']) {
			$id = (int) $_REQUEST['id'];
			$res = $modx->db->query('Select *  from '.$modx->getFullTableName('site_content').' where id='.$id);
			$doc = $modx->db->getRow($res);
		} else return;
		
		$key = $ec->checkAvailabilityInCofig($doc);			
		if ($key>-1){
			
			$ec->setCurrentConfig($key,$doc['id']);			
			$ec->getTable();
			exit();
		}
	}
	if ($modx->event->name=='OnPageNotFound'){
		
		if ($_REQUEST['q']=='easyCollection'){
			$id = (int) $_GET['id'];
			$key = (int) $_GET['key'];			
			$conf = $ec->config[$key];
			
			switch ($_GET['command']) {	
				case "table":
					$ec->getTable();
				break;
				
				case "crud":
					header("HTTP/1.1 200 OK");
					header('Content-Type: application/json');
						switch ($_GET['act']) {
							case "getData":								
								if (!$ec->currentConfig['oneTable']) $result = $ec->getDataDocuments($_POST);
								else $result = $ec->getDataOneTable($_POST);
							break;
							
							case "edit":								
								if (!$ec->currentConfig['oneTable']) $result = $ec->editDataDocuments($_POST);
								else $result = $ec->editDataOneTable($_POST);
							break;
							
							case "copy":								
								$result = $ec->copyDataDocuments($_POST);
							break;
							
							case "cut":								
								$result = $ec->cutDataDocuments($_POST);
							break;
							
							case "setCheckbox":
								if (!$ec->currentConfig['oneTable']) $result = $ec->setCheckboxDocuments($_POST);
								else $result = $ec->setCheckboxOneTable($_POST);
							break;
							
							case "delete":
								if (!$ec->currentConfig['oneTable']) $result = $ec->deleteDocuments($_POST);
								else $result = $ec->deleteOneTable($_POST);
							break;
							
							case "undelete":
								if (!$ec->currentConfig['oneTable']) $result = $ec->unDeleteDocuments($_POST);
								else $result = $ec->unDeleteOneTable($_POST);
							break;
							
							case "setIndex":
								if (!$ec->currentConfig['oneTable']) $result = $ec->setIndexDocuments($_POST);
							break;
							
						}					
					if ($result) echo json_encode(array('success'=>true,'data'=>$_REQUEST));
					else echo json_encode(array('msg'=>'Some errors occured.','data'=>$_REQUEST));
				break;
			}
			
			exit();	
				
		}
	}		