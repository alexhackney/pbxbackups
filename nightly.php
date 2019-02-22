<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$localFolders = explode(',', getenv('LOCAL_FOLDERS'));
$clientName = getenv('CLIENT_NAME');
$backupServer = getenv('BACKUP_SERVER');
$localRootDir = getenv('LOCAL_ROOT_DIRECTORY');
$username = getenv('REMOTE_SERVER_USERNAME');
$pubkey = getenv('BACKUP_SERVER_PUBKEY');
$privkey = getenv('BACKUP_SERVER_PRIVKEY');
$remoteRootDirectory = getenv('REMOTE_ROOT_DIRECTORY') . '/' . $clientName;
$flush = getenv('FLUSH');

$session = ssh2_connect($backupServer, 22);
if (!authenticate($session, $username, $pubkey, $privkey)) {

    return 'Didnt Work!';
}

$sftp = ssh2_sftp($session);

if (!checkForRemoteDirectory($sftp, $remoteRootDirectory)) {
    var_dump('Couldnt find or create remote Directory');
    exit;
}

foreach ($localDirectories as $directory) {
    //Set the working dir for backup files
    $localDir = $localRootDir . $directory;

    $destination = $remoteRootDirectory . $directory;

    checkForRemoteDirectory($sftp, $destination);

    checkForLocalDirectory($localDir);

    $filesCopied = processFiles($localDir, $destination, $session, $flush);

}

function authenticate($session, $username, $pubkey, $privkey)
{

    if (ssh2_auth_pubkey_file($session, $username, $pubkey, $privkey)) {
        return true;
    }

    return false;

}

function checkForRemoteDirectory($sftp, $directory)
{

    if (!file_exists('ssh2.sftp://' . $sftp . $directory)) {

        var_dump("$directory does not exist on backup server, creating now");

        return ssh2_sftp_mkdir($sftp, $directory);

    }

    return true;
}

function checkForLocalDirectory($directory)
{

    if (!file_exists($directory)) {
        var_dump("Directory Does Not Exist: $directory");
    }

    return true;
}

function processFiles($directory, $destination, $session, $flush)
{

    $processed = 0;
    //Get all the files in the Directory
    $files = scandir($directory);
    $totalFiles = count($files);

    foreach ($files as $file) {
        //If file is not a directory copy to backup server
        if (!is_dir($file)) {

            if (ssh2_scp_send($session, $directory . $file, $destination . $file)) {

                $processed++;
            }
        }
    }

    if ($flush) {

        var_dump("Deleting $processed copied files.");

        foreach ($files as $file) {
            if (!is_dir($file)) {
                unlink($directory . $file);
            }
        }
    }
    return $processed;
}
