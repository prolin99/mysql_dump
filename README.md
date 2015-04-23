

本程式修改自： blog.aajit.com/exportbackup-mysql-database-like-phpmyadmin/
改採 mysqli 
功用：在托管的主機中，只有 MySQL 使用的帳號，無法系統權限做備份機制。使用 phpMyAdmin 備份也只能採手動備份。
所以本程式本程式可以其它機器定時以網頁連結方式呼叫，在網站中做一個 SQL 備份壓縮檔 ZIP 。

連結方式
	http://.........../dump.php?user=mysql帳號&db=mysql資料庫

mysql 密碼，直接在程式中。
預設備份放到  backup 目錄，要給寫入的權限。
php 環境 5.6 以上( 設 $php56 = true)，可以設定 zip 密碼


目前已知問題：
	使用 mysqli 欄位 text , blob 等，傳回值是相同的，所以全部轉為 16 位元碼，輸出的 SQL 原始內容不容易查看，但匯回資料庫是正常的。

其他說明：
	本程式只是個人備份資料庫用途。試用前請自行測試評估，你的資料庫在匯出、匯入的情況能否正常運作。
