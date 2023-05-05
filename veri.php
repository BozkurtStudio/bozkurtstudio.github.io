<?php
header('Content-Type: text/html; charset=utf-8');
$curl_handle=curl_init();
curl_setopt($curl_handle, CURLOPT_URL,'http://www.thekodprogram.com/'); //Sitemizin URL adresi
curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl_handle, CURLOPT_USERAGENT, 'deneme');
//Burada Curl çalışıyor ve linkteki bütün içerikler query değişkeninde tutuluyor.
$query = curl_exec($curl_handle); 
curl_close($curl_handle);
//query değişkeninden almak istediğimiz kısmı bildiriyoruz. 
//Burada (.*?) ile belirtilen yer değişecek kısım.
//Yani anasayfada bir sürü yazı başlığı mevcut.
//Bizde bunların değişebileceğini derleyiciye (.*?) ile bildirdik.
$parcala_baslik='@<h2 class="post-box-title"><a href="(.*?)">(.*?)</a></h2>@si'; 
//Burada da query içinden istediğimiz kısmı parçalayıp $basliklar değişkenine atıyoruz.
preg_match_all($parcala_baslik, $query, $basliklar); 

$parcala_ozet='@<p>(.*?)</p>@si'; //Özet için de query içinde arayacağımız kısımı bildirdik.
preg_match_all($parcala_ozet, $query, $ozetler);

$parcala_resim='@<img width="(.*?)" height="(.*?)" src="(.*?)" class="(.*?)" alt="(.*?)">@si';
 //Resimler için de query içinde arayacağımız kısımı bildirdik.
//Bize android tarafında resimlerin linkleri lazım olduğu için src kısmını full değişken olarak belirttim.

preg_match_all($parcala_resim, $query, $resimler);

//Şimdi bu değişkenlerin bize getirdiği verileri ayrı ayrı array lere atayalım.
$gelen_basliklar=array();
for($i=0;$i<count($basliklar[2]);$i++)
{ 
//Başlıklar için attığım resimde 2.index te başlıkların stilsiz olarak geldiği görülüyor.O yüzden 2.indexteki verileri alıyorum.
 array_push($gelen_basliklar, 
			$basliklar[2][$i]);
}

$gelen_ozetler=array();
for($i=0;$i<count($ozetler[1]);$i++)
{
 array_push($gelen_ozetler, $ozetler[1][$i]); //Ozetler için attığım resimde 1. index işimize yarıyacak.
}


$gelen_resim_linkleri=array();
for($i=0;$i<count($resimler[3]);$i++)
{
//Resimler için de linkleri aldık ve dizimize atadık.
 array_push($gelen_resim_linkleri, $resimler[3][$i] ); 
}


$JSON["icerikler"]=array(); //JSON tipte döndüreceğimiz dizi


for($i=0;$i<count($gelen_basliklar);$i++)
{
 $temp["basliklar"]=array(); //JSON dizisinin içindeki alt diziler - basliklar,ozetler,resimlink
 $temp["ozetler"]=array();
 $temp["resimlink"]=array();
 array_push($temp["basliklar"], $gelen_basliklar[$i]);
 array_push($temp["ozetler"], $gelen_ozetler[$i]);
 array_push($temp["resimlink"],$gelen_resim_linkleri[$i]);
 array_push($JSON["icerikler"], $temp); //Son olarak da ana json dizimize verileri entegre ettik.

}


echo json_encode($JSON,JSON_UNESCAPED_UNICODE); 

?>
