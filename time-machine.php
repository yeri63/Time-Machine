<?php
    # Program: time-machine.php
    #    Date: 08-Dec-2020
    #  Author: gerry.walterscheid@gmail.com
    # Descrip: Simulate the time machine as seen in the Back to the Future move.

    session_start();
    header("Content-type: text/xml");

    $delay = 60;
    header("Refresh:$delay");

    $urlPath = "http://192.168.3.250/cisco/time-machine.php";

    # Used to adjust text display to match current phone model.
    function getCiscoPhoneModel() {
        return $_SERVER['HTTP_X_CISCOIPPHONEMODELNAME'] ?? 'unknown';
    }

    function getAge(DateTime $start, DateTime $end)
    {
        # Ensure $start is the earlier date.
        if ($start > $end) {
            $temp = $start;
            $start = $end;
            $end = $temp;
        }

        $current = clone $start;
        $years = 0;
        $months = 0;

        # Walk through each year.
        while (true) {
            $next = clone $current;
            $next->modify('+1 year');

            if ($next <= $end) {
                $current = $next;
                $years++; }
            else
                break;
        }

        # Walk through each month.
        # This part uses the actual calendar (28, 29, 30, or 31 days)
        while (true) {
            $next = clone $current;
            $next->modify('+1 month');

            if ($next <= $end) {
                $current = $next;
                $months++; }
            else
                break;
        }

        # Calculate remaining days, hours and minutes.
        # Since we've walked $current up to the final month,
        # the remaining gap is just the time difference.
        $diff = $current->diff($end);

        return [
            'years'   => $years,
            'months'  => $months,
            'days'    => $diff->days,
            'hours'   => $diff->h,
            'minutes' => $diff->i
        ];
    }

    # Recall prior variable states.
    $id = $_SESSION['id'] ?? "";

    # Prevent data corruption to next application.
    if ($id == "time-machine") {
        $year = $_SESSION['year'];    # destination year
        $month = $_SESSION['month'];  # destination month
        $day = $_SESSION['day'];      # destination day
        $hour = $_SESSION['hour'];    # destination hour
        $min = $_SESSION['min'];      # destination minute

        $num = $_SESSION['num']; }    # time unit value

    else {
        // $dest = new DateTime(date('Y-m-d H:i'));
        # $dest = new DateTime('0000-01-01 0:00');

        # Preset destination to right now.
        $dest = new DateTime(date('Y-m-d H:i'));

        $year = $dest->format('Y');
        $month = $dest->format('M');
        $day = $dest->format('d');
        $hour = $dest->format('H');
        $min = $dest->format('i');
        }

    $lastCmd = $_SESSION['cmd'] ?? 0; # User command.
    $mesg = "Enter new destination time.";

    # Get SoftKey selection and data collected.
    $cmd = $_GET['key'] ?? 0;
    $num = $_GET['num'] ?? 0;
    $num = $num + 0;

    # Get phone model name.
    $model = getCiscoPhoneModel();

    # Travel time message not needed on larger phone display.
    if ($model == "CP-7911G") 
        $mesg = "\t\t\t\t\tTravel Time"; 
    else
       $mesg = "";

    # Reset error message.
    $errMsg = "";

    # Process user input and update the destination year, month or day.
    switch($lastCmd) {
    case 1 : # Month
        if (($num < 1) || ($num > 12)) {
            $errMsg = "Enter month between 1 and 12.";
            $cmd = 1; }
        else {
            $month = $num;
            $cmd = 2; }
        break;

    case 2 : # Day
        switch($month) {

        case  2 : # Feb
            if (($year % 4 == 0) && ($year % 100 != 0) || ($year % 400 == 0))
                $monthDays = 29;
            else
                $monthDays = 28;
            break;

        case  4 : # Apr
        case  6 : # Jun
        case  9 : # Sep
        case 11 : # Nov
            $monthDays = 30;
            break;

        case  1 : # Jan
        case  3 : # Mar
        case  5 : # May
        case  7 : # Jul
        case  8 : # Aug
        case 10 : # Oct
        case 12 : # Dec
            $monthDays = 31;
            break;
        }

        if (($num < 1) || ($num > $monthDays)) {
            $errMsg = "Enter day between 1 and $monthDays.";
            $cmd = 2; }
        else
            $day = $num;
        break;

    case 3 : # Hour
        if ($num > 23) {
            $errMsg = "Enter hour between 0 and 23.";
            $cmd = 3; }
        else {
            $hour = $num;
            $cmd = 4; }
        break;

    case 4 : # Minute
        if ($num > 59) {
            $errMsg = "Enter minute between 0 and 59.";
            $cmd = 4; }
        else 
            $min = $num;
  	    break;

    case 5 : # Year
        if ($num < 1752) {
            $errMsg = "Invalid before the year 1752.";
            $cmd = 5; }
        else
            $year = $num;
        break;
    }

    # Prompt the user for information based on the key pressed.
    switch($cmd) {
    case 1 :
        $units = "Month";
        $mesg = "Enter the destination month.";
        break;

    case 2 :
        $units = "Day";
        $mesg = "Enter the destination day.";
        break;

    case 3 :
        $units = "Hour";
        $mesg = "Enter the destination hour.";
        break;

    case 4 :
        $units = "Min";
        $mesg = "Enter the destination minute.";
        break;

    case 5 :
        $units = "Year";
        $mesg = "Enter the destination year.";
        break;
    }

    # Update destination time and 3-char month for readability.
    $dest = new DateTime("$year-$month-$day $hour:$min");
    $Month = $dest->format('M');

    # If data entry error was made, update the message.
    if ($errMsg != "")
        $mesg = $errMsg;

    $today = new DateTime(date('Y-m-d H:i'));

    # Perform time travel calculations.
    $interval = getAge($today, $dest);

    $years = $interval['years'];
    $months = $interval['months'];
    $days = $interval['days'];
    $hours = $interval['hours'];
    $mins = $interval['minutes'];

    # Update IP Phone display.
    switch($cmd) {
    case 0 :
        echo "<CiscoIPPhoneText>\n";
        echo "  <Title>Time Machine</Title>\n";
        echo "  <Text>\n";

        # Adjust text position based on phone model.
        if ($model == "CP-7911G") {
            echo "Month\t\tDay\tYear\t  Hour\tMin\n";
            printf ("   %s\t\t\t %02d\t\t%04d\t\t%02d\t\t %02d\n", $Month, $day, $year, $hour, $min);
            printf ("     %02d\t\t\t %02d\t\t%04d\t\t%02d\t\t %02d\n", $months, $days, $years, $hours, $mins); }
        else {
            echo "\t\tMonth\t\tDay\tYear\t  Hour\tMin\n";
            printf ("\t\t\t%s \t\t %02d\t\t%04d\t\t %02d\t\t %02d\n", $Month, $day, $year, $hour, $min);
            echo "\t\t\t\t\t\t\tDestination Time\n";
            echo "\t\tMonth\t\tDay\tYear\t  Hour\tMin\n";
            printf ("\t\t\t %02d\t\t\t %02d\t\t%04d\t\t %02d\t\t %02d\n", $months, $days, $years, $hours, $mins);
            print "\t\t\t\t\t\t\t\t\tTravel Time\n"; }

        echo "  </Text>\n";

        echo "  <SoftKeyItem>\n";
        echo "    <Name>Mo/Day</Name>\n";
        echo "    <URL>$urlPath?key=1</URL>\n";
        echo "    <Position>1</Position>\n";
        echo "</SoftKeyItem>\n";

        echo "  <SoftKeyItem>\n";
        echo "    <Name>Hr/Min</Name>\n";
        echo "    <URL>$urlPath?key=3</URL>\n";
        echo "    <Position>2</Position>\n";
        echo "  </SoftKeyItem>\n";

        echo "  <SoftKeyItem>\n";
        echo "    <Name>Year</Name>\n";
        echo "    <URL>$urlPath?key=5</URL>\n";
        echo "    <Position>3</Position>\n";
        echo "  </SoftKeyItem>\n";

        echo "  <SoftKeyItem>\n";
        echo "    <Name>Exit</Name>\n";
        echo "    <URL>SoftKey:Exit</URL>\n";
        echo "    <Position>4</Position>\n";
        echo "  </SoftKeyItem>\n";

        echo "  <Prompt>".$mesg."</Prompt>\n";
        echo "</CiscoIPPhoneText>\n"; 
        break;

    case 1 :
    case 2 :
    case 3 :
    case 4 :
    case 5 :
        echo "<CiscoIPPhoneInput>\n";
        echo "  <Title>Time Machine</Title>\n";
        echo "  <URL method=\"post\">$urlPath</URL>\n";

        echo "  <InputItem>\n";
        echo "    <DisplayName>$units</DisplayName>\n";
        echo "    <QueryStringParam>num</QueryStringParam>\n";
        echo "    <DefaultValue> </DefaultValue>\n";
        echo "    <InputFlags>N</InputFlags>\n";
        echo "  </InputItem>\n";
  
        echo "  <SoftKeyItem>\n";
        echo "	  <Name>Update</Name>\n";
        echo "	  <URL>SoftKey:Submit</URL>\n";
        echo "	  <Position>2</Position>\n";
        echo "  </SoftKeyItem>\n";

        echo "  <SoftKeyItem>\n";
        echo "    <Name>Cancel</Name>\n";
        echo "    <URL>$urlPath?key=0</URL>\n";
        echo "    <Position>4</Position>\n";
        echo "  </SoftKeyItem>\n";

        echo "  <Prompt>$mesg</Prompt>\n";
        echo "</CiscoIPPhoneInput>\n";

        break;
    }

    # Save current variable state.
    $_SESSION['id'] = "time-machine";
    $_SESSION['year'] = $year;
    $_SESSION['month'] = $month;
    $_SESSION['day'] = $day;
    $_SESSION['hour'] = $hour;
    $_SESSION['min'] = $min;
    $_SESSION['num'] = $num;
    $_SESSION['cmd'] = $cmd;
?>

