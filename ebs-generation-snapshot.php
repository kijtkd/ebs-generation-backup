#!/usr/bin/php
<?php

error_reporting(E_ALL);
ini_set('display_errors','on');

// The display on the standard output the command to be executed
function command($command, &$output, &$returnVar)
{
  echo $command . "\n";
  exec( $command, $output, $returnVar );
  $output = join("\n",$output);
  if( $returnVar ){
    echo $output . "\n";
    exit($returnVar);
  }
}

// Convert json from the return value of the command
function json_command($command, &$output, &$returnVar)
{
  command( $command, $output, $returnVar );
  $output = json_decode( $output, true );
}

$generationVolumes = array();

// The Create Snapshot Backup Tag = ON.
json_command("aws ec2 describe-volumes", $volumes, $returnVar);
foreach($volumes['Volumes'] as $volume)
{
  if( !isset($volume['Tags']) ) continue;
  $volume['Name'] = $volume['VolumeId']; // default VolumeId
  $volume['Generation'] = 7; // default 7 Generation
  foreach( $volume['Tags'] as $tag ) $volume[$tag['Key']] = $tag['Value'];
  if( isset($volume['Backup']) && strtoupper($volume['Backup']) == 'ON' ){
    command("aws ec2 create-snapshot --volume-id {$volume['VolumeId']} --description 'Generation Backup {$volume['Name']}.'", $output, $returnVar);
    $generationVolumes[] = $volume;
  }
}

// Remove generation outdated.
json_command("aws ec2 describe-snapshots --owner-ids self", $snapshots, $returnVar);
$snapshots = array_filter( $snapshots['Snapshots'], function($var){
  return !empty($var['Description']) && preg_match('/^Generation Backup/',$var['Description']);
});
foreach( $generationVolumes as $volume ){
  $targetSnapshots = array_filter( $snapshots, function($var) use($volume){
    return strpos( $var['Description'], $volume['Name'] ) !== FALSE;
  });
  usort( $targetSnapshots, function($a, $b){
    return strtotime($a['StartTime']) < strtotime($b['StartTime']);
  });
  $targetSnapshots = array_slice( $targetSnapshots, $volume['Generation'] );
  foreach( $targetSnapshots as $snapshot ){
    command("aws ec2 delete-snapshot --snapshot-id {$snapshot['SnapshotId']}", $output, $returnVar);
  }
}
