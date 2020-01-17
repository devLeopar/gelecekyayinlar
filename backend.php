<?php
require __DIR__ . '/vendor/autoload.php';

/*if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}*/

/* Above lines is required to make token.json via using cli - CLI kullanarak drive'a giriş yapılıp token.json oluşturulması için öncelikle CLI ile çağrılsın*/

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
    $client->setScopes([Google_Service_Drive::DRIVE_METADATA_READONLY,\Google_Service_Sheets::SPREADSHEETS_READONLY]);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }
    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);
            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

// Get the API client and construct the service object. /////////////
$client = getClient(); 
$service = new Google_Service_Drive($client);
/* Above 2 lines indicates successful data from the object formed with google client api */


//getting desired file list from response - istenilen dosyaları drive response'dan çek
$optParams = array(
  'pageSize' => 500,
 // 'fields' => 'nextPageToken, files(*)', // tüm parametrelerini çağır / get all parameters of files,folders specified in search term
  'fields' => 'nextPageToken, files(id,name,createdTime)',
  'q' => "mimeType = 'application/vnd.google-apps.spreadsheet'" // only call excel spreadsheets - sadece excel sheetleri döndür
);

$results = $service->files->listFiles($optParams);

//Getting only ssport weekly spreadsheets into an array
$uevents = [];

if (count($results->getFiles()) == 0) {
    print "No files found.\n";
} else {
    foreach ($results->getFiles() as $file) {
        //printf("%s (%s) %s\n", $file->getName(), $file->getId(),$file->getCreatedTime()); //delete after use it
        $fname = $file->getName();
        $fid = $file->getId();
        $fCreatedTime = $file->getCreatedTime();

        if(stripos($fname,"weekly")>0 && stripos($fname,"sport")>0){
            array_push($uevents,["ev_name"=>$fname,"ev_id"=>$fid,"ev_createdTime"=>$fCreatedTime]);
        }
    }

//sorting uevents as creation time of spreadsheets new first using spaceshift which is above php 7.xx
// - uevents excelleri üretilen zamana göre sırala en son en başta ayrıca spaceshift karakteri(<=>) için php 7 ve üzeri olması gerek

usort($uevents,function($a,$b){return $b['ev_createdTime']<=>$a['ev_createdTime'];});
    
}




function getDataFromSheet($ids,$ssheet):array{

    $files =[];
    $rqFields = [];


//getting only row values formatted value and effective value because of speed//////////////////////////////////////////////
$range = 'A2:I'; //the range until image row
$sheet_fields = array(
    'fields'=> 'properties(title),sheets(data(rowData(values(formattedValue,effectiveFormat(textFormat)))))',
    'ranges' => $range,
);

    foreach($ids as $id){
        $sata = $ssheet->spreadsheets->get($id,$sheet_fields);
        array_push($files,array(
            'sheet_title' => $sata['properties']['title'],
            'sheet_data' => $sata['sheets'][0]['data'][0]['rowData'],
        ));
        //array_push($files,$sata);
    }
$datasheets = array(
 $files[0]['sheet_data'], //last created sheet
 $files[1]['sheet_data'],  // previous week sheet
);
////////////////////////////////üst bölümü fonksiyon dışına çıkarabilirim bazen takılmalar oluyor sebepsiz///////////////////////////////////

for($i = 0; $i < count($datasheets); $i++){
    foreach($datasheets[$i] as $row){
        //check image url string has correct form
        $image_url = $row['values'][8]['formattedValue'];
            if(isset($image_url) && strpos($image_url,'?id=') !== false ){
                $image_id = explode('?id=',$image_url)[1];
                $evNameTr = $row['values'][2]['formattedValue'];
                $dateDMY =  $row['values'][0]['formattedValue'];
                $dateClock = $row['values'][5]['formattedValue'];
                $date_dum = strtotime($dateDMY.' '.$dateClock. '+3 hours');

                // continue weekend this date thing

                echo 'test';

            }




       /*$image_id_url = isset($row['values'][8]['formattedValue'])?$row['values'][8]['formattedValue']:"";
            if(strpos($image_id_url,'id=')>0){
                $image_id_url_ex = explode('id=',$image_id_url);
            }
            else{
                $image_id_url_ex = "";
            }
        
        $image_id = isset($image_id_url_ex[1])?$image_id_url_ex[1]:"";
        $datas = array(
        'date' => $row['values'][0]['formattedValue'],
        'evNameTr' => $row['values'][2]['formattedValue'],
        'startTime' => $row['values'][5]['formattedValue'],
        'image_id' => $image_id,
        );
        array_push($rqFields,$datas);*/
    
}
}


 return $files;

}

/**
 * getting values from spreadsheets into an array for front-end usage
*/ 

$sheets = new \Google_Service_Sheets($client);

//$spreadsheet = $sheets->spreadsheets->get($spreadsheetId,$sheet_fields);
//last created 2 spreadsheet ids
$m_ids = array($uevents[9]['ev_id'],$uevents[10]['ev_id']);
$data = getDataFromSheet($m_ids,$sheets);




$spreadsheetId = $uevents[10]['ev_id'];


/*
* getting spreadsheets value and textFormat
*/


//$spreadsheet = $sheets->spreadsheets->get($spreadsheetId,['includeGridData' => true]); //return all parameters

$grid = $spreadsheet->getSheets();




/*$rows = $sheets->spreadsheets_values->get($spreadsheetId, $range, ['majorDimension' => 'ROWS']);
if (isset($rows['values'])) {
    foreach ($rows['values'] as $row) {
        /*
         * If first column is empty, consider it an empty row and skip (this is just for example)
         */
        /*if (empty($row[0])) {
            continue;
        }
        /**
         * Data array içine tüm değerleri al önemli olan set edilmemişse null döndürme 
         */
        /*$data[] = [
            'col-a' => isset($row[0])?$row[0]:"",
            'col-b' => isset($row[1])?$row[1]:"",
            'col-c' => isset($row[2])?$row[2]:"",
            'col-d' => isset($row[3])?$row[3]:"",
            'col-e' => isset($row[4])?$row[4]:"",
            'col-f' => isset($row[5])?$row[5]:"",
            'col-g' => isset($row[6])?$row[6]:"",
            'col-h' => isset($row[7])?$row[7]:"",
            'col-i' => isset($row[8])?$row[8]:"",
        ];*/

        /*
         * Now for each row we've seen, lets update the I column with the current date
         */
        /*$updateRange = 'I'.$currentRow;
        $updateBody = new \Google_Service_Sheets_ValueRange([
            'range' => $updateRange,
            'majorDimension' => 'ROWS',
            'values' => ['values' => date('c')],
        ]);
        $sheets->spreadsheets_values->update(
            $spreadsheetId,
            $updateRange,
            $updateBody,
            ['valueInputOption' => 'USER_ENTERED']
        );

        $currentRow++;
    }
    
}*/

echo "finishe"; //should deleted