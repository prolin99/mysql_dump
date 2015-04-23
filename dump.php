<?php
/*
//本程式修改自： blog.aajit.com/exportbackup-mysql-database-like-phpmyadmin/
//改採 mysqli 
//功用：在托管的主機中，只有 MySQL 使用的帳號，無法系統權限做備份機制。使用 phpMyAdmin 備份也只能採手動備份。
//所以本程式本程式可以其它機器定時以網頁連結方式呼叫，在網站中做一個 SQL 備份壓縮檔 ZIP 。
//
// 連結方式
//	http://.........../dump.php?user=mysql帳號&db=mysql資料庫
//
// mysql 密碼，直接在程式中。
// php 環境 5.6 以上( 設 $php56 = true)，可以設定 zip 密碼
//
//
//目前已知問題：
//	使用 mysqli 欄位 text , blob 等，傳回值是相同的，所以全部轉為 16 位元碼，輸出的 SQL 原始內容不容易查看，但匯回資料庫是正常的。
//
//其他說明：
//	本程式只是個人備份資料庫用途。試用前請自行測試評估，你的資料庫在匯出、匯入的情況能否正常運作。
*/

ini_set('display_errors',0);
//error_reporting(E_ALL);
set_time_limit(3000) ;


 	//設定區   -----------------------------------------------------
 
	$host = 'localhost' ;
	$username=$_GET['user'] ;
	$password='test' ;
 	$dbname= $_GET['db'] ;
 
 	
 	$drop=1;	//加入 drop table 指令
 	$max_lines = 100 ;		//同時寫入數，在匯入時才會比較快
 	$php56 = false ; 		//php 5.6 以上，才能做 ZIP 密碼設定
 	$zip_password='zippassword' ;
 	$backupFile= 'backup/'. $_GET['db'] . '.sql' ;
 	//-------------------------------------------------------------
 	
 	
	echo 'Backuping database....' . $dbname .'...<br />' ;  	
 	
	//Open a new connection to the MySQL server
	$mysqli = new mysqli($host,$username,$password,$dbname);

	//Output any connection error
	if ($mysqli->connect_error) {
		die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}
 	$mysqli->query("SET CHARSET utf8");



	@unlink( $backupFile );
	$backup='';
	
	$line=0 ;
	
	//有多少個資料表
	$results = $mysqli->query("SHOW TABLES FROM $dbname");
	while($tabs = $results->fetch_row()) {
		$droptbl=(($drop==1)?"DROP TABLE  IF EXISTS  `".$tabs[0]."`;":"");
	
		//$backup .= "--\n-- Table structure for `$tabs[0]`\n--\n\n".$droptbl."\nCREATE TABLE IF NOT EXISTS `$tabs[0]` (";
		$backup .= "--\n-- Table structure for `$tabs[0]`\n--\n\n".$droptbl."\n";
		
		$results2 = $mysqli->query("SHOW CREATE TABLE `$tabs[0]`  ");
		$tabsinfo =  $results2->fetch_row() ;
		$results2->free();	
		//資料表架構
		$backup .=$tabsinfo[1] . ";\n\n"  ; 
		$backup .= "--\n-- Data to be executed for table `$tabs[0]`\n--\n\n";
	
		//資料表的內容
		echo "<br /> dump  table $tabs[0]    !"; 
		$data_results =  $mysqli->query("SELECT * FROM `$tabs[0]`  ");
		
		$table_meta_set = 0 ;	
		$fh=fopen($backupFile,'a') or die("Backup not done! file error");		
		
		//欄位屬性，只做一次 (mysqli text blob 欄位視為相同252，所以文字全轉成 hex)
		$fields_info = $data_results->fetch_fields();
		
	 /*
		echo '<pre>' ;
		var_dump($fields_info) ;
		echo '</pre>' ;
 */
		
		$table_begin=1 ;
		
		while($dt =  $data_results->fetch_row() ) {
			$line++ ;
 			if  ($table_begin==1 ) 
				$backup .= "INSERT INTO `$tabs[0]` VALUES('". $mysqli->real_escape_string($dt[0])."'";
			else {
				//同時寫入筆數
				if  ( $line >= $max_lines ) {	
				  	$backup .= "; INSERT INTO `$tabs[0]` VALUES('".  $mysqli->real_escape_string($dt[0])."'";
				  	$line= 0 ;
				}else   	
					$backup .= ", ('".  $mysqli->real_escape_string($dt[0])."'";
			}	
			
			$table_begin= 0 ;
			
			for($i=1; $i<sizeof($dt); $i++) {
				//欄位屬性，為blob 
				if ( $fields_info[$i]->type == 252  ){
					if (empty($dt[$i]) && $dt[$i] != '0') 
						$backup .= " ,'' " ;
					else 	
						$backup .= ", 0x" . bin2hex($dt[$i])  ;
					//echo $meta->name . "<br />"  ;

				}else 	
					$backup .= ", '".  $mysqli->real_escape_string($dt[$i])."'";
			}
 
			$backup .= ") \n";
			
			//先部份存檔
			if  ($line ==0 ) {
				fwrite($fh, $backup);
				$backup='' ;
			}
		
		}	//各筆記錄		
		$data_results->free();	
		
		
		$backup .= "; \n";
		$backup .= "\n-- --------------------------------------------------------\n\n";		
		
		
	}  //各資料表
	$results->free();	
 	
	echo "<br />data dump sql  !"; 
	
	
	//直接寫入 .SQL
	//$fh=fopen($backupFile,'w') or die("Backup not done! file error");
	fwrite($fh, $backup);

	fclose($fh);
	echo "<br />Backup sql save !"; 
 
	// zip 檔
	$files = array( $backupFile);
	$zipname = $backupFile .'.zip';
	$zip = new ZipArchive;
	
	$zip->open($zipname, ZipArchive::CREATE|ZipArchive::OVERWRITE) ;
	//php 5.6以上才可加密碼
	if ($php56) 
    		$zip->setPassword("MySecretPassword")  ;

 
	foreach ($files as $file) {
  		$zip->addFile($file);
 	}
	$zip->close();
	unlink( $backupFile );		//去除 .SQL 檔
 

	echo "<br />Backup Complete !"; 
  	

?>