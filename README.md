AWS EBS Generation Backup
========================

1) Requirements for running
----------------------------------

Install awscli command to the available state.

PHP needs to be a minimum version of PHP 5.3.

JSON needs to be enabled.

2) Setting EBS Tag.
-------------------------------------

Set EBS Volume Tag

Backup     = ON

Name       = [VolumeName] (optional)

Generation = [GenerationNumber] (optional)

3) RUN
-------------------------------------

$php ebs-generation-backup.php
