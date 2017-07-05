<?php
/*
** Dm/G Mail-Merge 1.0 BETA
**
** Author:  David Michael Gregg [http://facebook.com/davegregg]
** License: GNU General Public License (GPL) version 3 [http://www.gnu.org/licenses/gpl-3.0.txt]
** Support: I do not guarantee that I will respond to support inquiries or feature requests.
**
**
** --TIPS----------------------------
**   * Variables can be used not only in the body of the email, but
**     in the subject line, sender's address, and reply-to address,
**     which are especially handy for dynamic branding
**   * There are a few hardcoded variables you should know:
**		'___dmgmmURL' returns the URL of the webversion of the email
**			(obviously, for providing a 'view-in-your-browser' copy
**			 of the email to recipients)
**		'___dmgmmTrackThis_' (note the trailing underscore) when added to
**			the beginning of a URL in an anchor link, the link will redirect
**			the user through the PHP of the webversion file which will track
**			the click in the view log and then forward the user on to the
**			destination URL
**		'___dmgmmRecipient' returns the recipient's email address
**			(in case you want to repeat a recipient's email address to them
**		'___dmgmmSender' returns the sender's email address
**			(handy for dynamic email signatures and the like)
**		'___dmgmmReplyTo' returns the reply-to email address
**			(handy for unsubscribe mailto links and the like)
**		'___dmgmmSubject' returns the reply-to email address
**			(handy for the HTML title tag in the webversion, for example)
**
**-TO-DO--------------------------------------------------------------------------------------
**		   - further documentation!
**			 - the records and logs
**		   - add logging OPTION!
**		   - memory optimization
**		   - add memory-limit-error prevention, by providing an option for the user to provide
**			 a list of files containing address-values sets, instead of list of sets directly
**		     into the form
**		   - strip whitespace and newlines from between address-values sets intelligently
**		   - add option to pull the template from a file
**		   - additional security feature(s)? Go back through with escaping in mind.
**		   - more error handling?
**		   - UI/UX improvements
**
**
*/

// Embarrassingly-simple pass check, requiring the query variable 'p' to be returned 
// with a valid value, else terminate the app
if ($_GET['p']!='pass'){die();}

// Run code only after the form has been submitted
if(isset($_POST['submit'])){

/****
GLOBAL DECLARATIONS
****/
	
	// Simple counters
	$successcount = 0;
	$failcount = 0;
	
	// Address-Values Set Delimiters
	$setdelimiter = $_POST['setdelimiter']; // ';;' by default
	$pairdelimiter = $_POST['pairdelimiter']; // '::' by default
	$valuedelimiter = $_POST['valuedelimiter']; // ',,' by default
	
	// Find current working directory and make sure it has write permissions
	$cwd = getcwd();
	chmod($cwd, 0775);
	
	// Create the /campaigns directory if necessary
	$campaignsdir = '/campaigns';
	if(!is_dir($cwd.$campaignsdir)){
			mkdir($cwd.$campaignsdir, 0775);
	}

	// Declare variables for the simple email elements
	$bodytemplate = $_POST['bodytemplate'];
	$subject = $_POST['subject'];
	$replyto = $_POST['replyto'];
	$sender = $_POST['sender'];
	
	// Prep campaign name from $subject for use in the file path by removing
	// characters invalid for filenames, replacing spaces with underscores,
	// and limiting the length
	$badchars = array_merge(
		array_map('chr', range(0,31)),
		array('<', '>', ':', "'", "\\", '/', '|', '?', '*')
	);
	$thiscampaign = str_replace($badchars, '', $subject);
	$thiscampaign = str_replace(' ', '_', $thiscampaign);
	if(strlen($thiscampaign) >= 100){
		$thiscampaign = substr($thiscampaign, 0, 100);
	}

	// If this campaign directory already exists, create an alternate directory name
	// by appending a bracketed number
	$thiscampaigndir = '/'.$thiscampaign;
	if(!is_dir($cwd.$campaignsdir.$thiscampaigndir)){
		mkdir($cwd.$campaignsdir.$thiscampaigndir, 0775);
	}
	else{
		$attempti = 1;
		$dirmade = 0;
		do{
			$attempti++;
			if(!is_dir($cwd.$campaignsdir.$thiscampaigndir.$attempti)){
				$thiscampaigndir = $thiscampaigndir.$attempti;
				mkdir($cwd.$campaignsdir.$thiscampaigndir, 0775);
				$dirmade = 1;
			}
		}
		while($dirmade != 1);
	}
	
	// Declare campaign path on server
	$campaignpath = $cwd.$campaignsdir.$thiscampaigndir;
	// Declare campaign web path
	$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
	$webpath = $protocol.'://'.$_SERVER['HTTP_HOST'];
	$webpath .= dirname($_SERVER['REQUEST_URI']).$campaignsdir.$thiscampaigndir;
	
	// Write HTML header for the results page
	$htmlheader = '<html><head><title>Dm/G Mail Merge results</title><link rel="stylesheet" href="css/style.css" type="text/css" media="all" /></head><body class="results">';
	echo $htmlheader;

	
/****
INITIAL SYNTAX HANDLING DIRECTLY FROM THE FORM
****/

	// Assign the unparsed delimited list of addresses and values to a variable
	// to prevent repeated calls to $_POST
	$delimitedAddieValPairs = $_POST['delimitedAddieValPairs'];
	
	// If the unparsed delimited list of address-values sets ends in ";;" (by default),
	// then trim it off, because a delimiter is not needed at the end of a string and
	// will cause an error later
	if (substr($delimitedAddieValPairs, strlen($delimitedAddieValPairs) - 2, 2) === $setdelimiter){
		$delimitedAddieValPairs = substr($delimitedAddieValPairs, 0, -2);
	}
	
	// Separate each address-values set into an array, delimiting by ";;" (by default)
	$AddieValPairsArray = explode($setdelimiter, $delimitedAddieValPairs);

	// Assign the unparsed comma-delimited list of variables to prevent repeated calls to $_POST
	$delimitedVars = $_POST['delimitedVars'];
	// If the unparsed comma-delimited list of variables ends in ",", then trim it off
	if (substr($delimitedVars, strlen($delimitedVars) - 1, 1) === ","){
		$delimitedVars = substr($delimitedVars, 0, -1);
	}
	// Separate the provided variables, delimiting by ","
	$varsArray = explode(',', $delimitedVars);
	
	
/****
PRE-LOOP LOG HANDLING
****/
	
	// Write first row of CSV log file to the log record variable
	$logrecord = 'UID,Timestamp,Recipient,Server Path,Web Path,'.$delimitedVars."\r\n";
	
	// Create webversion views log CSV file, write first row, and close file
	$webversionviewsfile = fopen($campaignpath.'/webversionviews.csv', 'w+');
	$thiswebversionview = "UID,Recipient,Accessed,Action\r\n";
	fwrite($webversionviewsfile, $thiswebversionview);
	fclose($webversionviewsfile);
	
/****
THE LOOP
****/
	// Process email individually
	foreach($AddieValPairsArray as $singleAddieValPair) {
		$body = $bodytemplate;
		//echo '$singleAddieValPair = '.$singleAddieValPair."\n<br>";
/****
IN-LOOP MEMORY PURGE OF POTENTIALLY LARGE ARRAYS
****/
		// Ensure arrays free up any residual memory from the last pass of the loop
		// and are empty before proceeding
		$valsArray=NULL;
		$valsArray=array();
		$thisAddieValPairArray=NULL;
		$thisAddieValPairArray=array();
		
		// Check syntax to ensure only one occurrence of the "::" delimiter (by default)
		//if (substr_count($singleAddieValPair,$pairdelimiter) != 1 || strpos($singleAddieValPair,$pairdelimiter.$pairdelimiter)===false){
		if (substr_count($singleAddieValPair,$pairdelimiter) != 1){
			echo '<br><br><span class="fail">!!! ERROR !!! A mistake was found in the syntax for the Address-Values set below. Please carefully check for redundant or misplaced delimiters in all sets.<br><br><blockquote>'.$singleAddieValPair.'</blockquote></span><br><br>Process aborted.<br><br>';
			die();
		}
		// Separate address from values, delimiting by "::" (by default)
		$thisAddieValPairArray = explode($pairdelimiter, $singleAddieValPair);
		//echo '-- $thisAddieValPairArray[0] = '.$thisAddieValPairArray[0]."\n<br>";
		// Assign the address to the final email recipient variable "$recipient"
		// and check syntax of address
		$recipient = trim($thisAddieValPairArray[0]);
		//echo '-- $recipient = '.$recipient."\n<br>";
		if (substr_count($recipient,'@') != 1){
			echo '<br><br><span class="fail">!!! ERROR !!! Invalid email address found. Please carefully check all addresses. The error was discovered in the following address:<br><blockquote>'.$recipient.'</blockquote></span><br><br>Process aborted.<br><br>';
			die();
		}
		
		// Separate the values for this email into $valsArray, delimiting by ",," (by default)
		$valsArray = explode($valuedelimiter, $thisAddieValPairArray[1]);
		
/****
IN-LOOP LOG AND RECORD PREPARATION
****/
		// Declare unique ID and timestamp for this email
		$uniqueid = uniqid();
		$timestamp = str_replace(',','',date('r'));
		// Build email record filename and URL
		$recordfn = $campaignpath.'/'.$uniqueid.'.php';
		$recordurl = $webpath.'/'.$uniqueid.'.php';
		$recordurlwithparameter = $recordurl.'?a='.$recipient;
		
		// Log this email into the campaign log file variable
		//???$values = str_replace($valuedelimiter,',',$thisAddieValPairArray[1]);
		$logrecord .= $uniqueid.','.$timestamp.','.$recipient.',"'.$recordfn.'","'.$recordurlwithparameter;

/****
IN-LOOP ERROR CHECK FOR MISMATCHED VARIABLE-VALUE COUNTS
****/	
		// Error-check for mismatched variable-value counts
		if (count($varsArray) != count($valsArray)){
			$varvalerror = '<br><br><span class="fail">!!! ERROR !!! The number of variables you supplied is not equal to the number of values you supplied. Verify your data and syntax.</span><br><br>';
			$varvalerror .= 'You provided the following ' . count($varsArray) . ' variable(s): ';
			$varvalerror .= $_POST['delimitedVars'] . '<br><br>';
			$varvalerror .= 'You provided the following ' . count($valsArray) . ' value(s): ';
			foreach($valsArray as $singleVal){
				$varvalerror .= $singleVal . ', ';
			}
			echo $varvalerror;
			exit();
		}

/****
IN-LOOP VARIABLE-VALUE REPLACEMENT LOOP
****/		
		// Perform a search-and-replace loop for variable-value pairs
		for ($i = 0; $i < count($varsArray); ++$i) {
			// Replace the provided variable with the associated value
			// and reassign the product to the variables
			$body = str_replace($varsArray[$i],$valsArray[$i],$body);
			$subject = str_replace($varsArray[$i],$valsArray[$i],$subject);
			$replyto = str_replace($varsArray[$i],$valsArray[$i],$replyto);
			$sender = str_replace($varsArray[$i],$valsArray[$i],$sender);
			// Append this variable to the log file entry
			$logrecord .= '","'.$valsArray[$i];
			//echo '-- "$varsArray[$i] = $valsArray[$i]" evaluates to '.$varsArray[$i].' = '.$valsArray[$i]."\n<br>";
		}
		
		// Replace hardcoded variables with the proper values
		$body = str_replace('___dmgmmURL',$recordurlwithparameter,$body);
		$body = str_replace('___dmgmmRecipient',$recipient,$body);
		$body = str_replace('___dmgmmSender',$sender,$body);
		$body = str_replace('___dmgmmReplyTo',$replyto,$body);
		$body = str_replace('___dmgmmSubject',$subject,$body);
		$body = str_replace('___dmgmmTrackThis_',$recordurlwithparameter.'&u=',$body);
		
		// If magic_quotes is enabled on your server, it will automatically escape single
		// quote ('), double quote ("), backslash (\), and NUL characters in $_POST data
		// Use stripslashes() to unescape those characters
		if (get_magic_quotes_gpc()){
			$body = stripslashes($body);
		}
		
		// Wrap up the last of the assignments and build the mail headers
		$headers = 'From: ' . $sender . "\n";
		$headers .= 'Reply-To: '. $replyto . "\n";
		$headers .= 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-Type: text/html; charset=ISO-8859-1' . "\n";
			/*  Note the double-quotes in the above lines used to trigger PHP to read "\n"
			**  as escape codes rather than plain text */

		// Send the email and increment the success counter; the mail() function returns TRUE or FALSE
		if (mail($recipient, $subject, $body, $headers)) {
			//echo '-- "mail($recipient, $subject, $body, $headers)" evaluates to mail('.$recipient.', '.$subject.', --begin body--<br>'.$body.'<br>--end body---<br>, '.$headers."\n\n<br><br>";
			$successcount++;
		}
		// If mail() fails, increment the failure counter and report troublesome email to user
		else {
			$failcount++;
			$failerror = '<br><br><span class="fail">There was a problem sending the following email:</span><br><br>';
			$failerror .= 'From: ' . $sender . '<br>';
			$failerror .= 'To: ' . $recipient . '<br>';
			$failerror .= 'Reply-To: ' . $replyto . '<br>';
			$failerror .= 'Subject: ' . $subject . '<br><br>';
			$failerror .= 'Headers: ' . $headers . '<br><br>';
			$failerror .= 'Body: ' . $body . '<br>';
			echo $failerror;
		}
		
/****
IN-LOOP CAMPAIGN RECORDS FILE HANDLING
****/
		// Append a new line to the log variable to complete the row in the log file CSV
		$logrecord .= '"'."\r\n";
		
		// Open the PHP file for recording the email HTML
		$htmlrecordfile = fopen($recordfn, 'w+') or die('There was a problem creating the file record for an email to ' . $recipient);
		$htmlrecord = '<?php ';
		
		// If the query variable 'a' does return the recipient address,
		// then refuse to render the HTML
		$htmlrecord .= "\n\nif (!isset(\$_GET['a']) || \$_GET['a']!=='$recipient'){die();}\n\n";

		// If recipient address check passes and an external URL is passed in parameter 'u',
		// then log and redirect
		$htmlrecord .= "elseif (isset(\$_GET['u']) && !empty(\$_GET['u'])){\n";
		$htmlrecord .= "\$webversionviewsfile = fopen('$campaignpath/webversionviews.csv', 'a+');\n";
		$htmlrecord .= "\$thisclick = '$uniqueid,$recipient,'.str_replace(',','',date('r')).\",\\\"link clicked ({\$_GET['u']})\\\"\\r\\n\";\n";
		$htmlrecord .= "fwrite(\$webversionviewsfile, \$thisclick);\n";
		$htmlrecord .= "fclose(\$webversionviewsfile);\n";
		$htmlrecord .= "echo \"<META HTTP-EQUIV=\\\"Refresh\\\" Content=\\\"0; URL={\$_GET['u']}\\\">\";\n";
		$htmlrecord .= "die();}\n\n";
	
		// Else, record a webversion-view to the log
		$htmlrecord .= 'else{';
		$htmlrecord .= "\$webversionviewsfile = fopen('$campaignpath/webversionviews.csv','a+');";
		$htmlrecord .= "\$thiswebversionview = '$uniqueid,$recipient,'.str_replace(',','',date('r')).',webversion viewed'.\"\\r\\n\";";
		$htmlrecord .= 'fwrite($webversionviewsfile, $thiswebversionview);';
		$htmlrecord .= 'fclose($webversionviewsfile);}';
		
		// Close PHP tag and write HTML email data for webversion viewing
		$htmlrecord .= "\n\n ?>\n\n";
		$htmlrecord .= $body;
		// Append hidden email data at the end of the file
		$htmlrecord .= "\n\n".'<!--'."\n\n";
		$htmlrecord .= 'TIMESTAMP: '.$timestamp."\n";
		$htmlrecord .= 'RECIPIENT: '.$recipient."\n";
		$htmlrecord .= 'SUBJECT: '.$subject."\n";
		$htmlrecord .= $headers."\n";
		$htmlrecord .= 'SUPPLIED VARIABLES (DELIMITED BY ","): '.$delimitedVars."\n";
		$htmlrecord .= 'SUPPLIED VALUES (DELIMITED BY "'.$valuedelimiter.'"): '.$thisAddieValPairArray[1]."\n";
		$htmlrecord .= "\n".'-->';
		// Write the PHP, HTML, and hidden email data to the file
		fwrite($htmlrecordfile, $htmlrecord);
		fclose($htmlrecordfile);
		
		$singleAddieValPair = NULL;
	}

/****
POST-LOOP MEMORY PURGE OF POTENTIALLY LARGE ARRAYS
****/
	// Frees arrays from memory now that they aren't needed
	$valsArray = NULL;
	$thisAddieValPairArray = NULL;
	$AddieValPairsArray = NULL;
	/*unset($valsArray);
	unset($thisAddieValPairArray);
	unset($AddieValPairsArray);*/
	
/****
POST-LOOP WRITE CAMPAIGN LOG FILE RECORDS ALL AT ONCE
****/

	// Open the log file, write the log variable, and close the file
	$logfn = $campaignpath.'/campaignlog.csv';
	$logfile = fopen($logfn, 'w+') or die('There was a problem creating or writing data to the campaign log file for the following campaign: <em>'.$subject.'</em>');
	fwrite($logfile, $logrecord);
	fclose($logfile);
	
/****
SUCCESS AND ERROR REPORTING
****/

	// Report successes and failures to the user
	$completionmsg = '<br><br><strong>Process complete.</strong>';
	if ($successcount != 0){
		$completionmsg .= '<br><br><span class="success">You successfully sent ' . $successcount . ' email(s).</span>';
	}
	elseif ($successcount == 0 && $failcount != 0){
		$completionmsg .= '<br><br><span class="fail">!!! ERROR !!! EVERY mail() attempt FAILED! Check the code for logical errors and check your input for character encoding or any possible escaping issues.</span>';
	}
	elseif ($successcount == 0 && $failcount == 0){
		$completionmsg .= '<br><br><span class="fail">!!! ERROR !!! $successcount and $failcount were both ZERO! Check the code for logical errors and check your input for character encoding or any possible escaping issues.</span>';
	}
	if ($failcount != 0){
		$completionmsg .= '<br><br><span class="fail">You failed to send ' . $failcount . ' email(s).</span>';
	}
	echo $completionmsg;
	
	// Write HTML footer for the results page
	$htmlfooter = '</body></html>';
	echo $htmlfooter;
	
	// Prevent the below HTML from rendering again after the form has been submitted
	die();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Dm/G Mail Merge</title>
	<link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
	<script src="http://www.google.com/jsapi" type="text/javascript"></script>
    <script type="text/javascript">
        google.load("jquery", "1.3.2");
    </script>
	<script type="text/javascript" src="js/jquery.form.js"></script>
</head>
<body>

    <div id="page-wrap">
    
    <h1>Dm/G Mail Merge</h1>

	<form action="<? echo($PHP_SELF) ?>" method="post" id="dmg-mm-form">
	
		<div class="rowElem">
            <label for="delimitedVars">Variables<span>SYNTAX: Variables are delimited by single commas (,) without surrounding spaces. Place these variables wherever you would like in your email template and list them here, noting the order with which they are listed. The values you provide in the next section must be listed in the same order. Variables should be named uniquely in order to make sure legimate blocks of text or HTML aren't replaced with the values from the section. In the following example, variable names include three underscores as a method of ensuring uniqueness.<br /><strong>EXAMPLE:</strong><div>___variable1,___variable2,___variable3,___variable4</div></span></label>
			<textarea cols="40" rows="4" name="delimitedVars"></textarea>
        </div>
        
        <div class="rowElem">
            <label for="delimitedAddieValPairs">Recipient Addresses with Variable Values<span>SYNTAX: Per-email address-and-values sets are delimited by two consecutive semi-colons (;;). Delimit associated values from an address by following the address with two consecutive colons (::). Delimit values by two consecutive commas (,,) in the order in which the variables were supplied above. The number of values provided each email must match exactly the number of variables provided above. In the following example are address-and-values sets for two emails. Both sets contain four values to associate with four variables above. Values must not contain the three character combinations reserved for delimiters: doubled semi-colons, doubled colons, and doubled commas, by default. See the options area below to change these delimiters, if necessary.<br /><strong>EXAMPLE:</strong><div>user1@domain1.com::valueforvariable1,,valueforvariable2,,valueforvariable3,,valueforvariable4;;user2@domain2.com::valueforvariable1,,valueforvariable2,,valueforvariable3,,valueforvariable4</div></span></label>
			<textarea cols="40" rows="8" name="delimitedAddieValPairs" class="required" minlength="2"></textarea>
        </div>

		<div class="rowElem">
            <label for="subject">Subject</label>
			<input type="text" id="subject" name="subject" class="required" minlength="2" value="" />
        </div>

		<div class="rowElem">
            <label for="sender">From<span>Spam filters will flag an email as unauthorized if the address is not allowed in the SPF (Sender Policy Framework) record in your domain name's zone file. Email addresses truly hosted on your server are automatically allowed by the SPF, but if you wish to send an email with a sender address that does not actually correspond to an inbox on your server or that corresponds to an inbox on a different server, then you must add this email address to your SPF record. See documentation for how to do this on a <a href="http://support.godaddy.com/help/680" target="_new">GoDaddy</a> domain, on a <a href="http://kb.mediatemple.net/questions/658/How+can+I+create+an+SPF+record+for+my+domain%3F" target="_new">MediaTemple</a> domain, for servers running <a href="http://www.google.com/search?hl=en&q=~add+OR+~create+%22spf+record%22+OR+%22spf+records%22+plesk" target="_new">Plesk</a>, and for servers running <a href="http://www.google.com/search?hl=en&q=~add+OR+~create+%22spf+record%22+OR+%22spf+records%22+cpanel" target="_new">cPanel</a>. For further details, visit <a href="http://www.openspf.org/" target="_new">The SPF Project</a>.</span></label>
			<input type="text" id="sender" name="sender" class="required" minlength="2" value="" />
        </div>

		<div class="rowElem">
            <label for="replyto">Reply-To</label>
			<input type="text" id="replyto" name="replyto" class="required" minlength="2" value="" />
        </div>
		
		<div class="rowElem" id="bodytemplateArea">
		  <label for="bodytemplate">Email Body Template<span>For help constructing a compatible HTML email template, see <a href="http://htmlemailboilerplate.com/" target="_new">HTML Email Boilerplate</a> and CampaignMonitor's articles, especially those on <a href="http://www.campaignmonitor.com/design-guidelines/" target="_new">email design tips</a> and <a href="http://www.campaignmonitor.com/css/" target="_new">cross-client CSS support</a>.</span></label>
		  <textarea cols="40" rows="32" name="bodytemplate" class="required" minlength="2"></textarea>
        </div>
		
		<div class="rowElem">
		  <label> &nbsp; </label>
		  <input type="submit" name="submit" id="submit" value="Send" />
        </div>
        <br />
		<br />
		<br />
		<h2>ADVANCED OPTIONS &mdash;</h2>
		<div class="rowElem" id="optionsarea">
		  <label for="setdelimiter">Change Set Delimiter (";;" by default)</label>
		  <input type="text" id="setdelimiter" name="setdelimiter" minlength="1" value=";;" />
		  <label for="pairdelimiter">Change Pair Delimiter ("::" by default)</label>
		  <input type="text" id="pairdelimiter" name="pairdelimiter" minlength="1" value="::" />
		  <label for="valuedelimiter">Change Value Delimiter (",," by default)</label>
		  <input type="text" id="valuedelimiter" name="valuedelimiter" minlength="1" value=",," />
		  <br/ ><br />
		  <label for="disablelogging">Toggle Logging</label>
		  <input type="checkbox" name="disablelogging" value="campaignlog" />Disable the campaign log<br />
		  <input type="checkbox" name="disablelogging" value="autowebversions" />Disable automatic webversion creation<br />
		  <input type="checkbox" name="disablelogging" value="webversionview" />Disable webversion-view logging<br />
		  <input type="checkbox" name="disablelogging" value="linkclicks" />Disable link-clicks logging
        </div>
		
		
	</form>
	<br /><br /><br /><br /><br /><br /><br /><br />
	</div>

</body>

</html>
