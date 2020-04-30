<?php

require "dbinfo.php";

// Opens a connection to the MySQL server
$connection = new mysqli($server, $username, $password, $database);

if (!$connection) {  die('Not connected : ' . $mysqli->connect_error);}

mysqli_set_charset($connection,"utf8");

$probeQuery = "SELECT client_mac FROM clients WHERE vendor IS NULL OR vendor LIKE ''";
$probeResult = $connection->query($probeQuery);

$clientsWithNullVendor = $probeResult->num_rows;

if ($clientsWithNullVendor > 0) {

  echo $clientsWithNullVendor . " rows left without vendor (before the round just completed)<br>";
  echo "Running through loop up to 500 times, updating 500 next vendors<br>";
  echo "All clients matching a particular OUI will be updated at once<br>";
  echo "Press F5 to run script again";
  echo "<br>----------------------------------------------------------------<br>";
  echo "Found vendors:<br><br>";

  $stopLoop = 0;

  while ($stopLoop == 0) {

    $query = "SELECT client_mac FROM clients WHERE vendor IS NULL OR vendor LIKE '' limit 1";
    $result = $connection->query($query);

    $row = $result->fetch_assoc();
    $mac_from_db_trimmed = substr($row["client_mac"], 0, 8);

    $url = "https://macvendors.co/api/vendorname/" . urlencode($mac_from_db_trimmed);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);

    if($response !== "No vendor") {

      echo $mac_from_db_trimmed . " - " . $response . "<br>";

      $query2 = 'UPDATE clients SET vendor="' . $response . '" WHERE client_mac LIKE "' . $mac_from_db_trimmed . ':__:__:__";';
      $result2 = $connection->query($query2);

    } else {
      echo $mac_from_db_trimmed . " - UNKNOWN<br>";

      $query3 = "UPDATE clients SET vendor='UNKNOWN' WHERE client_mac LIKE '" . $mac_from_db_trimmed . ":__:__:__';";
      $result3 = $connection->query($query3);
    }

    $endOfLoopQuery = "SELECT client_mac FROM clients WHERE vendor IS NULL OR vendor LIKE '' limit 1";
    $endOfLoopResult = $connection->query($endOfLoopQuery);
    $clientsWithNullVendor = $endOfLoopResult->num_rows;

    if ($clientsWithNullVendor == 0) {
      $stopLoop = 1;
      echo "<br>No more clients left with missing vendor! Stopping loop<br>";
    }

    $loopCount++;

    if ($loopCount >= 500) {
      $stopLoop = 1;
    }
  } //END while loop

  echo "Loop completed. Press F5 to start another round";

} //END if rows with blank vendor > 0

else {
  echo "No more clients left with missing vendor!";
}

?>
