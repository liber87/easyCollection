<?php
$config = array(
	 array(			
		'id'=>'-1',
		'name'=>'Просмотр лога MODX',
		'fields' => 
		array(									
			'timestamp'=>array('name'=>'Время','type'=>'date','sortable'=>true,'width'=>200),	
			'username'=>array('name'=>'Пользователь','type'=>'text','sortable'=>true,'width'=>200),	
			'action'=>array('name'=>'act','type'=>'text','width'=>200),	
			'itemid'=>array('name'=>'itemid','type'=>'text','width'=>200),	
			'itemname'=>array('name'=>'Событие','type'=>'text','width'=>200),	
			'message'=>array('name'=>'Сообщение','type'=>'text','width'=>200),	
			'ip'=>array('name'=>'ip','type'=>'text-break','width'=>200),	
			'useragent'=>array('name'=>'useragent','type'=>'text','width'=>200)
						
		),			
		'oneTable'=>'manager_log',
		'orderBy'=>'id desc',
		'search_'=>' `email` like "%[+txt_search+]%" or `fullname` like "%[+txt_search+]%"',
		'prepare_'=>'users.easyCollection.dl',
		'button'=>'@CODE: <a id="Button5" class="btn btn-success" href="index.php?a=87" target="main"><i class="fa fa-floppy-o"></i><span>Добавить событие</span></a>'
	)
);
