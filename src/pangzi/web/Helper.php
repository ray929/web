<?php
namespace pangzi\web;
class Helper {
    static function redirect(string $url ,?string $msg='' ,bool $top=false) {
        ob_clean();        
		$ostr = time().mt_rand(1000,9999);
		if($top) {
			$location = 'top.location';
		} else {
			$location = 'location';
		}
		?>
        echo 
<<<HTMLREDIRECT
{$msg}
<script type="text/javascript">
    {$location} = '{$url}';
</script>
HTMLREDIRECT;
		<?
		exit;
    }

}