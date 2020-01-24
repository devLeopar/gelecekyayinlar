<?php
//include plugins_url(basename(dirname(dirname(__FILE__)))) .'/data.php';
include 'data.php';
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function upcoming_events(){
    global $data;
    $simdi = strtotime('now');
    $carousel = "";
//start output buffering
ob_start();    
echo "<div class='plus-carousel-container'>\n";
    echo "<div class='plus-carousel'>\n";
if(isset($data)){
    foreach($data as $item){
        if($item['time']>=$simdi){
    $carousel .= <<< EX
            <div class="carousel-container">
                <a class="plus-image-link" href="javascript:;"><img class="plus-image" src="https://drive.google.com/uc?export=view&id={$item['imageId']}"></a>
                <h3 class="plus-title">{$item['evNameTr']}</h3>
                <p class="plus-date">{$item['date']}</p>
            </div>
EX;
    } //endif
} //endforeach
} // end of if data[] has values
    echo $carousel;
 
            
echo        "</div>\n";
echo "</div>";

//getting which has exposed after foreach
$content = ob_get_contents();
ob_end_clean();
return $content;

}

add_shortcode('plus_events','upcoming_events');

?>
  


