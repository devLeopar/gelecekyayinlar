<?php

//direk browser üzerinden çağırabilmek için tüm wp fonksiyonları ile birlikte
//bunu wordpress ajax ekleyerekte yapabiliyoruz anladığım kadarıyla but dont know 
require_once(dirname(dirname(dirname(__DIR__))) . '/wp-load.php'); //wordpress-ismi/wp-load.php wp yüklenmediği için wp global kullanamıyoruz.


require __DIR__ . '/vendor/autoload.php';
//define('WP_PLUGIN_DIR',"C:\wamp64\www\mysite\wp-content\plugins");
/*if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}*/

/* Above lines is required to make token.json via using cli - CLI kullanarak drive'a giriş yapılıp token.json oluşturulması için öncelikle CLI ile çağrılsın*/

//these are file directories located in same plugin folder
/*$credentials_path = plugins_url(basename(dirname(dirname(__FILE__)))) .'/credentials.json';
$token_path = plugins_url(basename(dirname(dirname(__FILE__)))) .'/token.json';
$data_path = plugins_url(basename(dirname(dirname(__FILE__)))) .'/data.php';*/

$credentials_path = WP_PLUGIN_DIR. '/canliyayinlar-ssportplus/credentials.json';
$token_path = WP_PLUGIN_DIR. '/canliyayinlar-ssportplus/token.json';
$data_path = WP_PLUGIN_DIR. '/canliyayinlar-ssportplus/data.php';




/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient($cr_path,$tk_path)
{
    //these are absolute path of the files in this plugin directory
    /*$credentials_path = $cr_path;
    $token_path = $tk_path;*/

    $credentials_path = $cr_path;
    $token_path = $tk_path;

    
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
    $client->setScopes([Google_Service_Drive::DRIVE,\Google_Service_Sheets::SPREADSHEETS_READONLY]);
    $client->setAuthConfig($credentials_path);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = $token_path;
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }
    /*else{ // sadece browser üzerinden çağırmak için deneme aslı(https://github.com/googleapis/google-api-php-client)
        if (isset($_GET['code'])) {
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $client->setAccessToken($token);

            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
    }*/
    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl); //burayı AWS putty'de CLI'ye basmıyor 
            print 'Enter verification code: ';                                  //ilerde başka bir yöntem düşünülebilir belki
            $authCode = trim(fgets(STDIN));
			//$authCode = '4/vwHDpqncBA1b0Yzu_q8Ar2i_IbxCzpy_0IrMrlZ6298j2WCO7NBTp_w'; //direk kodu yapıştırmayı denedik olmadı :)
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
$client = getClient($credentials_path,$token_path); 
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
        $fname = $file->getName();
        $fid = $file->getId();
        $fCreatedTime = $file->getCreatedTime();
        //get only Ssport Weekly spreadsheets on google drive
        if(stripos($fname,"weekly")>0 && stripos($fname,"sport")>0){
            array_push($uevents,["file_name"=>$fname,"file_id"=>$fid,"file_createdTime"=>$fCreatedTime]);
        }
    }

//check whether uevents has correct spreadsheets which has name of "Ssport Weekly xx Month ...."
if(!empty($uevents)){


    //sorting uevents as creation time of spreadsheets new first using spaceshift which is above php 7.xx
    // - uevents excelleri üretilen zamana göre sırala en son en başta ayrıca spaceshift karakteri(<=>) için php 7 ve üzeri olması gerek
    usort($uevents,function($a,$b){return $b['file_createdTime']<=>$a['file_createdTime'];});
    

/**
 * Get data from multiple spreadsheets on google drive
 * @return rqFields as required fileds Event Title,Event Data and Image Id
 */
function getDataFromSheet($ids,$ssheet,$service):array{

    $rqFields = [];
    $dateEn =['January','February','March','April','May','June','July','August','September','October','November','December',
               'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $dateTr =['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık',
               'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar']; 
              
    $now = time() + 10800; // UTC olarak zamanı al ve +3 saat ekle(10800 saniye for GMT+3)
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
        //check image url string has correct form(has ?id= part in url) and is not null or empty
        // Bu row[values] yerleri değişecek yeni sisteme geçince muhtemelen check et
        $image_url = isset($row['values'][8]['formattedValue'])?$row['values'][8]['formattedValue']:""; 
            if($image_url !== "" && strpos($image_url,'?id=') !== false ){
                $dateDMY =  $row['values'][0]['formattedValue'];
                $dateClock = $row['values'][5]['formattedValue'];
                
                if( $dateClock === '00:00:00'){
                $date_dum = strtotime($dateDMY.' '.$dateClock. '+27 hours'); //27 saat eklendi çünkü 00 zaman geçişi excel'de bir önceki günün başı gibi kabul ediliyor.
                }
                else{
                $date_dum = strtotime($dateDMY.' '.$dateClock. '+3 hours'); //adding +3 hours to be GMT+3 
                }
                $strike = $row['values'][0]['effectiveFormat']['textFormat']['strikethrough'] || $row['values'][3]['effectiveFormat']['textFormat']['strikethrough'];
                //check if events' time passed or not and strikethrough of it by Melida
                if($date_dum>=$now && !$strike){
                //bu satır altına performans için resimleri indir küçült ve wp->upload et ve url al diyebiliriz
                //dedik bile :)
                $image_id = explode('?id=',$image_url)[1]; 
                //pass image ID and authorized service to get wordpress uploaded image URL
                $media_image_link = G_drive_to_wordpress($image_id,$service); 
            


                //event name al türkçe - ki bu [2] değişecek
                $evNameTr = $row['values'][2]['formattedValue'];

                $excDateEn = date("j F l - H:i",$date_dum);
                $exDateTr = str_replace($dateEn,$dateTr,$excDateEn);

                //getting if event cancelled or not by looking strikethrough property set by Melida 


                
                        $datas = array(
                            'evNameTr' => $evNameTr, // Event türkçe ismi
                            'date' => $exDateTr, // 23 Ocak Perşembe - 15:30 formatında tarih 
                            'imageId' => $image_id, // google drive resim id
                            'time' => $date_dum,
                            'image_local_url' => $media_image_link,
                        );
                        //add required fields to the array
                        array_push($rqFields,$datas);
                    
                

            } //eğer etkinlik zamanı geçmişse ve üzeri çizili ise end of if

        }
              
} //end of foreach
} //end of for


 return $rqFields;

}


/**
 * Getting image content from Google drive
 * Upload them into wordpress media library
 * Highly optimized single function :) rather than 2
 */
function G_drive_to_wordpress($image_id,$service)
{
    $filename = $image_id .'.jpg';
    $upload_dir = wp_upload_dir();

    //getting correct file location
    //with proper permissions
    if ( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
      }
      else {
        $file = $upload_dir['basedir'] . '/' . $filename;
      }

    $file_exist = file_exists($file);
    $file_size = ($file_exist)?filesize($file):0; //check whether file found
    //if file does not exist OR
    //file exist but equal to 0 byte
if(!$file_exist || $file_size == 0){
    //getting content of G.Drive file with its ID 
    $content = $service->files->get($image_id, array("alt" => "media"));
    //getting actual image data
    $image_data = $content->getBody();
    //put this data into file which is on upload_dir
    file_put_contents($file, $image_data);
    //check file type based on its name
    $wp_filetype = wp_check_filetype( $filename, null );
//describe wordpress in which file will be uploaded
$attachment = array(
  'post_mime_type' => $wp_filetype['type'],
  'post_title' => sanitize_file_name( $filename ),
  'post_content' => '',
  'post_status' => 'inherit'
);
//creating attachment id
$attach_id = wp_insert_attachment( $attachment, $file );
//requiring image.php to work with generate attachment things
require_once( ABSPATH . 'wp-admin/includes/image.php' );
// generate post(type=image) based on id,attachment(specs) to $file
$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
//adding metadata to this image based on its id
wp_update_attachment_metadata( $attach_id, $attach_data );
//getting image(large) url [this can be thumbnail,medium,large,original and those values can be arranged into wp-admin/settings/media]
$image_url = wp_get_attachment_image_src($attach_id,'large')[0]; // at 0 position url 1 and 2 its sizes etc

return $image_url;
} //end of if(file exist or file size = 0 byte)

//if file exist and size is not equal to 0
else{
global $wpdb; //getting already connected wordpress database
// search in filename into database to get those image id
$image_id_wp = intval( $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/$filename'" ) );
// get those large image url based on its id
$image_url = wp_get_attachment_image_src($image_id_wp,'large')[0];
return $image_url;
}

}//end of junction


/**
 * getting values from spreadsheets into an array for front-end usage
*/ 

$sheets = new \Google_Service_Sheets($client);

/**
 * Şimdiki gün ve aya göre doğru 2 sheeti belirleyip
 * Onları döndüren fonksyion
 * @return idz arrayi içinde tutuyor
 */
function determineId($file):array{

    $idz = [];
    $cYear = date("Y");
    $nYear = $cYear + 1;
    $cTime = strtotime(date("d M"));
    $datecTime = date('d/m/Y - H:i',$cTime);
    
    for($i = 0; $i < count($file);$i++){
        $filename = $file[$i]['file_name'];
        $posly = strpos($filename,'ly');
        $poshyp = strpos($filename,'-');
        $fdateOne = substr($filename,$posly+2,$poshyp-($posly+2));
        $fdateTwo = substr($filename,$poshyp+1);
        preg_match('/[a-zA-Z]+/',$fdateOne,$matchOne);
        preg_match('/[a-zA-Z]+/',$fdateTwo,$matchTwo);
    
        if(!empty($matchOne)){
            //yıl geçişi haftası
            if($matchOne[0] == 'Dec' && $matchTwo[0] == 'Jan'){
                $min = strtotime($fdateOne.$cYear);
                $max = strtotime($fdateTwo.$nYear);
                }
            else{
                $min = strtotime($fdateOne.$cYear);
                $max = strtotime($fdateTwo.$cYear);
                }
        }
        else{
            $min = strtotime($fdateOne.$matchTwo[0].$cYear);
            $max = strtotime($fdateTwo.$cYear);
        }
    
        // Eğer şuan gün hangi hafta arasına geliyor ise o ve ondan önceki (daha yeni) olan sheet id'yi al
        if($min<=$cTime && $cTime<=$max){
            if($i == 0){
                array_push($idz,$file[$i]['file_id']);
            break;
            }
            else{
            array_push($idz,$file[$i]['file_id'],$file[$i-1]['file_id']);
            break;
            }
    
        }
    
    
    }
    
    
    return $idz;
}



$m_ids = determineId([$uevents[0],$uevents[1],$uevents[2]]);
$data = getDataFromSheet($m_ids,$sheets,$service);

//put data[] into data.php to further use in frontend.php
$dataExp = var_export($data,true);
$var = "<?php\n\n\$data = $dataExp;\n\n?>";
file_put_contents($data_path, $var);




} //end of if $uevents is not empty

else{

    printf("Ssport Weekly Spreadsheetleri bulunamadı,kontrol ediniz!");
}




} //end of else (so drive has spreadsheets)





