<?php
// Redirect to new location - preserve query parameters
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: views/settings.php' . $query);
exit;
?>
