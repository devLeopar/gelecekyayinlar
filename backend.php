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
        //get only Ssport Weekly spreadsheets on google drive
        if(stripos($fname,"weekly")>0 && stripos($fname,"sport")>0){
            array_push($uevents,["file_name"=>$fname,"file_id"=>$fid,"file_createdTime"=>$fCreatedTime]);
        }
    }

//sorting uevents as creation time of spreadsheets new first using spaceshift which is above php 7.xx
// - uevents excelleri üretilen zamana göre sırala en son en başta ayrıca spaceshift karakteri(<=>) için php 7 ve üzeri olması gerek

usort($uevents,function($a,$b){return $b['file_createdTime']<=>$a['file_createdTime'];});
    
}


/**
 * Get data from multiple spreadsheets on google drive
 * @return rqFields as required fileds Event Title,Event Data and Image Id
 */
function getDataFromSheet($ids,$ssheet):array{

    $rqFields = [];
    $dateEn =['January','February','March','April','May','June','July','August','September','October','November','December',
               'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $dateTr =['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık',
               'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar']; 
               
    $now = strtotime('now');
    $datasheets = [];



//getting only row values formatted value and effective value because of speed//////////////////////////////////////////////
$range = 'A2:I'; //the range until image row
$sheet_fields = array(
    'fields'=> 'properties(title),sheets(data(rowData(values(formattedValue,effectiveFormat(textFormat)))))',
    'ranges' => $range,
);


/**
 * Getting https calls from google drive api for multiple sheets
 */
    foreach($ids as $id){
        $sata = $ssheet->spreadsheets->get($id,$sheet_fields);
        $files = array(
            'sheet_title' => $sata['properties']['title'],
            'sheet_data' => $sata['sheets'][0]['data'][0]['rowData'],
        );

        array_push($datasheets,$files['sheet_data']);

    }

////////////////////////////////üst bölümü fonksiyon dışına çıkarabilirim bazen takılmalar oluyor sebepsiz///////////////////////////////////

for($i = 0; $i < count($datasheets); $i++){
    foreach($datasheets[$i] as $row){
        //check image url string has correct form
        $image_url = $row['values'][8]['formattedValue']; //bu line'ı hiç resim yok ama diğer satırlar varsa hata veriyor bakıcam
            if(isset($image_url) && strpos($image_url,'?id=') !== false ){
                $image_id = explode('?id=',$image_url)[1];
                $evNameTr = $row['values'][2]['formattedValue'];
                $dateDMY =  $row['values'][0]['formattedValue'];
                $dateClock = $row['values'][5]['formattedValue'];
                $date_dum = strtotime($dateDMY.' '.$dateClock. '+3 hours'); //adding +3 hours to be GMT+3
                $excDateEn = date("d F l - H:i",$date_dum);
                $exDateTr = str_replace($dateEn,$dateTr,$excDateEn);
                //getting if event cancelled or not by looking strikethrough property set by Melida 
                $strike = $row['values'][0]['effectiveFormat']['textFormat']['strikethrough'] || $row['values'][3]['effectiveFormat']['textFormat']['strikethrough'];

                //check if events' time passed or not 
                if($date_dum>=$now){
                    if(!$strike){
                        $datas = array(
                            'evNameTr' => $evNameTr,
                            'date' => $exDateTr,
                            'imageId' => $image_id,
                        );
                        //add required fields to the array
                        array_push($rqFields,$datas);
                    }
                }

            }

            
              
}
}


 return $rqFields;

}

/**
 * getting values from spreadsheets into an array for front-end usage
*/ 

$sheets = new \Google_Service_Sheets($client);


//last created 2 spreadsheet ids - to sort them into correct order put previous and next (düzgün dizmek için önceki tarihte olanı başa al $uevents[1]['ev_id'] )
// Buraya sadece 2 file seçmek için file isimlerinden haftaya dayalı seçim yapan fonksiyon yazabilirim
$m_ids = array($uevents[2]['file_id'],$uevents[1]['file_id'],$uevents[0]['file_id']);
$data = getDataFromSheet($m_ids,$sheets);





echo "finishe"; //should deleted