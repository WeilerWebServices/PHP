
cd /home/testfestreports/testRunDir

tar -cvzf tf.tar *

cp tf.tar /var/www/results.testfest.php.net/public_html/publishresults/

cd /var/www/results.testfest.php.net/public_html/publishresults/ 

tar -xvf tf.tar


