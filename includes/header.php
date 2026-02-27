<?php
// $base is set by the calling page before including this file
if (!isset($base)) $base = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="UC Computer Laboratory Management System — Track sit-ins, manage sessions, and monitor lab activities in real time." />
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>UC CompLab System</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
</head>
<body>