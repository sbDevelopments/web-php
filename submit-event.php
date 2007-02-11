<?php
// $Id$
$_SERVER['BASE_PAGE'] = 'submit-event.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/include/prepend.inc';
include_once $_SERVER['DOCUMENT_ROOT'] . '/include/posttohost.inc';
include_once $_SERVER['DOCUMENT_ROOT'] . '/include/email-validation.inc';
site_header("Submit an Event");

// No errors, processing depends on POST data
$errors = array();
$process = (boolean) count($_POST);

// Avoid E_NOTICE errors on incoming vars if not set
$vars = array(
    'sday', 'smonth', 'syear', 'eday',
    'emonth', 'eyear', 'recur', 'recur_day'
);
foreach ($vars as $varname) {
    if (!isset($_POST[$varname]) || empty($_POST[$varname])) {
        $_POST[$varname] = 0;
    }
}
$vars = array(
	'type', 'country', 'category', 'email', 'url', 'ldesc', 'sdesc'
);
foreach($vars as $varname) {
	if (!isset($_POST[$varname])) {
		$_POST[$varname] = "";
	}
}

// We need to process some form data
if ($process) {

    // Clean up magic quotes, if they were inserted
    if ($MQ) {
        foreach ($_POST as $k => $v) {
            $_POST[$k] = stripslashes($v);
        }
    }

    // Clean and validate data
    if (!is_emailable_address($_POST['email'])) {
        $errors[] = 'You must supply a valid email address.';
    }
  
    $_POST['sdesc'] = trim($_POST['sdesc']);
    if (!$_POST['sdesc']) {
        $errors[] = "You must supply a short description of the event.";
    }

    $_POST['ldesc'] = trim(strip_tags($_POST['ldesc'], '<a><i><b><br /><p>'));
    $_POST['ldesc'] = preg_replace("/(style|on\\w+?)\s*=\s*(\"|').+?\\2/i", "", $_POST['ldesc']);
    if (!$_POST['ldesc']) {
        $errors[] = "You must supply a long description of the event.";
    }

    $valid_schemes = array('http','https','ftp');
    $pu = parse_url($_POST['url']);

    $_POST['url'] = trim($_POST['url']);
    $pu['host'] = trim($pu['host']);
    if (!$_POST['url']) {
        $errors[] = "You must supply a URL with more information about the event.";
    }
    elseif (!in_array($pu['scheme'], $valid_schemes) || empty($pu['host'])) {
        $errors[] = "The URL you supplied was invalid.";
    }

    if (!$_POST['country']) {
        $errors[] = 'You must specify a country for the event.';
    }

    if (!$_POST['category']) {
        $errors[] = 'You must specify a category for the event.';
    }

    if (!checkdate($_POST['smonth'], $_POST['sday'], $_POST['syear'])) {
      $errors[] = "You must specify a valid start date.";
    }
    else {
        $sdate = mktime(0, 0, 1, $_POST['smonth'], $_POST['sday'], $_POST['syear']);
        if ($sdate < time()) {
            $errors[] = "You must specify a start date that is in the future.";
        }
    }

    if ($_POST['type'] == 'multi' && !checkdate($_POST['emonth'], $_POST['eday'], $_POST['eyear'])) {
        $errors[] = "You must specify a valid end date for a multi-day event.";
    }
    elseif ($_POST['type'] == 'multi' && checkdate($_POST['smonth'], $_POST['sday'], $_POST['syear'])) {
        $sdate = mktime(0, 0, 1, $_POST['smonth'], $_POST['sday'], $_POST['syear']);
        $edate = mktime(0, 0, 1, $_POST['emonth'], $_POST['eday'], $_POST['eyear']);
        if ($edate < time()) {
            $errors[] = "You must specify an end date that is in the future.";
        }
        elseif ($edate < $sdate) {
            $errors[] = "You must specify an end date that is after the start date.";
        }
    }

    if ($_POST['type'] == 'recur' && !($_POST['recur'] && $_POST['recur_day'])) {
        $errors[] = "You must specify a valid day of the month for a recurring event.";
    }

    if (preg_match("/submit/i", $action)) {
        // Submit to master.php.net
        $result = posttohost("http://master.php.net/entry/event.php", $_POST);
        if ($result) {
            $errors[] = "There was an error processing your submission: $result";
        }
        if (!$errors) {
            echo "<p>\n Thank you for your submission! You should hear back soon\n" .
                 " as to whether your event has been accepted for inclusion in\n" .
                 " our calendar.\n</p>";
            site_footer();
            exit;
        }
    }

    if (!$errors) {
        echo "<p>\n The following is a preview of your event submission.\n" .
             " Please double-check it to make sure all of the information is correct.\n</p>";
    }
}

// No form data to process
else {
    echo "<p>\n Have an upcoming PHP user group meeting? Holding a PHP training session?\n" .
         " Submit your event here, and after it has been approved, it will be listed on\n" .
         " the PHP.net homepage and appear in our full event listings.\n</p>";
}

// Display errors if found
if ($errors) { display_errors($errors); }

// Generate days and months arrays for form
for ($i = 1; $i <= 7; $i++) {
    $days[$i] = strftime('%A', mktime(12, 0, 0, 4, $i, 2001));
}
for ($i = 1; $i <= 12; $i++) {
    $months[$i] = strftime('%B', mktime(12, 0, 0, $i, 1, 2001));
}

// Possibilities to recur
$re = array(
     1 => 'First',
     2 => 'Second',
     3 => 'Third',
     4 => 'Fourth',
    -1 => 'Last',
    -2 => '2nd Last',
    -3 => '3rd Last'
);

// If we have data, display preview
if ($process) {
    echo "<p><strong>Preview:</strong></p>\n";
    display_event($_POST);
    echo "<p><strong>Change:</strong></p>\n";
}

?>
<form action="/submit-event.php" method="post">
<table border="0" cellpadding="3" class="standard">
 <tr>
  <th class="subr">Start Date</th>
  <td>
   <select name="smonth"><option></option><?php display_options($months, $_POST['smonth'])?></select>
   <input type="text" name="sday" size="2" maxlength="2" value="<?php echo htmlentities($_POST['sday'])?>" />
   <input type="text" name="syear" size="4" maxlength="4" value="<?php echo $_POST['syear'] ? htmlentities($_POST['syear']) : date("Y")?>" />
   <input type="radio" id="single" name="type" value="single"<?php if ($_POST['type'] == 'single' || !$_POST['type']) echo ' checked="checked"';?> />
   <label for="single">One day (no end-date required)</label>
  </td>
 </tr>
 <tr>
  <th class="subr">End Date</th>
  <td>
   <select name="emonth"><option></option><?php display_options($months, $_POST['emonth'])?></select>
   <input type="text" name="eday" size="2" maxlength="2" value="<?php echo htmlentities($_POST['eday'])?>" />
   <input type="text" name="eyear" size="4" maxlength="4" value="<?php echo $_POST['eyear'] ? htmlentities($_POST['eyear']) : date("Y")?>" />
   <input type="radio" id="multi" name="type" value="multi"<?php if ($_POST['type'] == 'multi') echo ' checked="checked"';?> />
   <label for="multi">Multi-day event</label>
  </td>
 </tr>
 <tr>
  <th class="subr">OR Recurring</th>
  <td>
   <select name="recur"><option></option><?php display_options($re, $_POST['recur'])?></select>
   <select name="recur_day"><option></option><?php display_options($days, $_POST['recur_day'])?></select>
   <input type="radio" id="recur" name="type" value="recur"<?php if ($_POST['type'] == 'recur') echo ' checked="checked"';?> />
   <label for="recur">Recurring (every month)</label>
  </td>
 </tr>
 <tr>
  <th class="subr">Short Description</th>
  <td><input type="text" name="sdesc" class="max" value="<?php echo htmlentities($_POST['sdesc'])?>" size="32" maxlength="32" /></td>
 </tr>
 <tr>
  <th class="subr">URL</th>
  <td><input type="text" name="url" size="40" maxlength="128" class="max" value="<?php echo htmlentities($_POST['url'])?>" /></td>
 </tr>
 <tr>
  <th class="subr">Country</th>
  <td>
   <select name="country" class="max">
    <option value="">- Select a country -</option>
    <?php display_options($COUNTRIES, $_POST['country']);?>
   </select>
  </td>
 </tr>
 <tr>
  <th class="subr">Event Category</th>
  <td>
   <select name="category" class="max">
<?php
	$cat = array("- Select a category -", "User Group Event", "Conference", "Training");
        display_options($cat, $_POST['category']);
?>
   </select>
  </td>
 </tr>
 <tr>
  <th class="subr">Email</th>
  <td>
   <input type="text" name="email" size="40" maxlength="128" class="max" value="<?php echo htmlentities($_POST['email'])?>" /><br />
   <small>This email address is only used to contact you about the listing, it will not displayed along with the listing.</small>
  </td>
 </tr>
 <tr>
  <th class="subr">Long Description</th>
  <td><textarea name="ldesc" cols="60" rows="10" wrap="virtual" class="max"><?php echo htmlentities($_POST['ldesc']);?></textarea></td>
 </tr>
 <tr>
  <th colspan="2">
    <input type="submit" name="action" value="Preview" />
<?php if ($process && count($errors) == 0) {?>
    <input type="submit" name="action" value="Submit" />
<?php }?>
  </th>
 </tr>
</table>
</form>
<?php
site_footer();

// Display an option list with one selected
function display_options($options, $current)
{
    foreach ($options as $k => $v) {
        echo '<option value="', $k, '"',
             ($k == $current ? ' selected="selected"' : ''),
             '>', htmlentities($v), "</option>\n";
    }
}

/* vim: set et ts=4 sw=4 ft=php: : */
?>
