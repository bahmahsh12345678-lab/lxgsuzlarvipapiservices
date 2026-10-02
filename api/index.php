<?php
// ============================================================
// LOGSUZLAR VIP - TEK DOSYA API (Tüm sorgular)
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(120);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// ============================================================
// ORTAK YARDIMCI FONKSİYONLAR
// ============================================================
function cek($url, $timeout = 8) {
    static $cache = array();
    if (isset($cache[$url])) return $cache[$url];

    $UAS = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, $UAS[array_rand($UAS)]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $body = @curl_exec($ch);
    @curl_close($ch);

    if (!$body) { $cache[$url] = null; return null; }
    $json = json_decode($body, true);
    if (!is_array($json)) { $cache[$url] = null; return null; }
    if (isset($json['auth'])) unset($json['auth']);
    if (isset($json['auth_alt'])) unset($json['auth_alt']);
    $cache[$url] = $json;
    return $json;
}

function proxy_cek($target, $params) {
    // ARO proxy tipi (ajaxsystems)
    $url = $target;
    if (!empty($params)) $url .= '?' . http_build_query($params);

    $UAS = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    );
    $ua = $UAS[array_rand($UAS)];

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json, text/plain, */*',
            'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8',
            'Referer: https://apiv2.ajaxsystems.fun/',
            'Origin: https://apiv2.ajaxsystems.fun',
        ),
        CURLOPT_ENCODING => '',
    ));
    $body = curl_exec($ch);
    curl_close($ch);
    if (!$body) return null;
    $body = preg_replace('/"auth"\s*:\s*"@jessy_php"\s*,?\s*/i', '', $body);
    $body = preg_replace('/"auth_alt"\s*:\s*"@jessy_php"\s*,?\s*/i', '', $body);
    $json = json_decode($body, true);
    if (is_array($json)) {
        $json['api_script_sahibi'] = '@fbxnext';
        $json['instagram'] = '@logsuzlarpanel';
        $json['tiktok'] = '@logsuzlar.inc';
    }
    return $json;
}

function temizle($arr) {
    if (!is_array($arr)) return $arr;
    $yeni = array();
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            $v = temizle($v);
            if (!empty($v)) $yeni[$k] = $v;
        } else {
            if ($v !== null && $v !== '' && $v !== 'null' && $v !== 'NULL') $yeni[$k] = $v;
        }
    }
    return $yeni;
}

function cinsiyet($ad) {
    if (!$ad) return 'E';
    $ad = strtoupper(str_replace(
        array('i','ı','ş','ğ','ü','ö','ç'),
        array('I','I','S','G','U','O','C'), $ad));
    $kadin = array('AYSE','FATMA','EMINE','HATICE','ZEYNEP','ELIF','MERYEM','SERIFE','SULTAN',
        'MERAL','MUZEYYEN','DURRI','KADRE','SEVIM','NUR','GUL','GULSUM','NAZLI','SEMA',
        'SEVDA','MELEK','BURCU','ECE','SELMA','AYLIN','ESRA','DILEK','OZLEM','HULYA',
        'LEYLA','SEVGI','DUYGU','EDA','BETUL','MELTEM','PINAR','CEREN','BAHAR','IREM',
        'ZEHRA','ZUHAL','SUKRAN','ZELIHA','NESRIN','SIBEL','MELIHA','MELIKE','KADRIYE');
    $p = explode(' ', trim($ad));
    if (in_array($p[0], $kadin)) return 'K';
    if (preg_match('/(YE|NA|LE|SE|GUL|NUR|HAN|CAN|SU|AY|EL)$/', $p[0])) return 'K';
    return 'E';
}

function tarihParcala($tarih) {
    if (!$tarih) return null;
    if (preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $tarih, $m))
        return array('gun'=>(int)$m[1],'ay'=>(int)$m[2],'yil'=>(int)$m[3]);
    if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $tarih, $m))
        return array('gun'=>(int)$m[3],'ay'=>(int)$m[2],'yil'=>(int)$m[1]);
    return null;
}

function yasHesapla($dogum) {
    if (!$dogum) return null;
    $d = tarihParcala($dogum);
    $b = tarihParcala(date('Y-n-j'));
    if (!$d || !$b) return null;
    $yas = $b['yil'] - $d['yil'];
    if ($b['ay'] < $d['ay']) $yas--;
    elseif ($b['ay'] == $d['ay'] && $b['gun'] < $d['gun']) $yas--;
    return $yas;
}

function kisiCikar($k) {
    if (!is_array($k)) return null;
    $tc = isset($k['KimlikNo']) ? $k['KimlikNo'] : (isset($k['TC']) ? $k['TC'] : null);
    $ad = isset($k['Isim']) ? $k['Isim'] : (isset($k['AD']) ? $k['AD'] : null);
    $soyad = isset($k['Soyisim']) ? $k['Soyisim'] : (isset($k['SOYAD']) ? $k['SOYAD'] : null);
    $dogum = isset($k['DogumTarihi']) ? $k['DogumTarihi'] : (isset($k['DOGUM_YILI']) ? $k['DOGUM_YILI'] : null);
    $yas = isset($k['YAS']) ? $k['YAS'] : null;
    $il = isset($k['NufusIl']) ? $k['NufusIl'] : (isset($k['MEMLEKETIL']) ? $k['MEMLEKETIL'] : null);
    $ilce = isset($k['NufusIlce']) ? $k['NufusIlce'] : (isset($k['MEMLEKETILCE']) ? $k['MEMLEKETILCE'] : null);
    $anneAd = isset($k['AnneIsim']) ? $k['AnneIsim'] : (isset($k['ANNEADI']) ? $k['ANNEADI'] : null);
    $anneTc = isset($k['AnneKimlikNo']) ? $k['AnneKimlikNo'] : (isset($k['ANNETC']) ? $k['ANNETC'] : null);
    $babaAd = isset($k['BabaIsim']) ? $k['BabaIsim'] : (isset($k['BABAADI']) ? $k['BABAADI'] : null);
    $babaTc = isset($k['BabaKimlikNo']) ? $k['BabaKimlikNo'] : (isset($k['BABATC']) ? $k['BABATC'] : null);
    if (!$tc && !$ad) return null;
    return array('tc'=>$tc,'ad'=>$ad,'soyad'=>$soyad,'dogum'=>$dogum,'yas'=>$yas,
        'il'=>$il,'ilce'=>$ilce,'anne_ad'=>$anneAd,'anne_tc'=>$anneTc,
        'baba_ad'=>$babaAd,'baba_tc'=>$babaTc);
}

function listeCikar($veri) {
    $liste = array();
    if (!is_array($veri)) return $liste;
    if (isset($veri[0]) && is_array($veri[0])) {
        foreach ($veri as $k) { $n = kisiCikar($k); if ($n) $liste[] = $n; }
        return $liste;
    }
    foreach (array('data','veri','sonuc','result','kisiler','aile','sulale','ailepro') as $alan) {
        if (isset($veri[$alan]) && is_array($veri[$alan])) {
            foreach ($veri[$alan] as $k) {
                if (is_array($k)) { $n = kisiCikar($k); if ($n) $liste[] = $n; }
            }
        }
    }
    return $liste;
}

// ============================================================
// ROUTING - ?type=xxx
// ============================================================
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$tc = isset($_GET['tc']) ? preg_replace('/[^0-9]/', '', $_GET['tc']) : '';
$gsm = isset($_GET['gsm']) ? preg_replace('/[^0-9]/', '', $_GET['gsm']) : '';
$ad = isset($_GET['ad']) ? trim($_GET['ad']) : '';
$soyad = isset($_GET['soyad']) ? trim($_GET['soyad']) : '';
$dogum = isset($_GET['dogum']) ? trim($_GET['dogum']) : '';

if (!$type) {
    echo json_encode(array('ok'=>false,'hata'=>'type parametresi gerekli','ornek'=>'?type=tc&tc=11111111110'), JSON_UNESCAPED_UNICODE);
    exit;
}

$sonuc = array('ok' => true);

// ============================================================
// 1) TC - Ajax proxy
// ============================================================
if ($type == 'tc' || $type == 'tcpro' || $type == 'tapu' || $type == 'adres' ||
    $type == 'eokul' || $type == 'isyeri' || $type == 'sgk' ||
    $type == 'aile' || $type == 'ailepro' || $type == 'sulale' ||
    $type == 'tcgsm' || $type == 'gsmtc' || $type == 'adaparsel') {

    $targets = array(
        'tc'       => 'https://apiv2.ajaxsystems.fun/tc.php',
        'tcpro'    => 'https://apiv2.ajaxsystems.fun/tcpro.php',
        'tapu'     => 'https://apiv2.ajaxsystems.fun/tapu.php',
        'adres'    => 'https://apiv2.ajaxsystems.fun/adres.php',
        'eokul'    => 'https://apiv2.ajaxsystems.fun/eokul.php',
        'isyeri'   => 'https://apiv2.ajaxsystems.fun/isyeri.php',
        'sgk'      => 'https://solidarksystems.alwaysdata.net/sgk.php',
        'aile'     => 'https://apiv2.ajaxsystems.fun/aile.php',
        'ailepro'  => 'https://solidarksystems.alwaysdata.net/ailepro.php',
        'sulale'   => 'https://apiv2.ajaxsystems.fun/sulale.php',
        'tcgsm'    => 'https://apiv2.ajaxsystems.fun/tcgsm.php',
        'gsmtc'    => 'https://apiv2.ajaxsystems.fun/gsmtc.php',
        'adaparsel'=> 'https://apiv2.ajaxsystems.fun/adaparsel.php',
    );
    $params = $_GET;
    unset($params['type']);
    $json = proxy_cek($targets[$type], $params);
    if ($json) { echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
    echo json_encode(array('ok'=>false,'hata'=>'Baglanti hatasi')); exit;
}

// ============================================================
// 2) ADSOYAD - solidarksystems proxy
// ============================================================
if ($type == 'adsoyad') {
    $params = $_GET;
    unset($params['type']);
    $json = proxy_cek('https://solidarksystems.alwaysdata.net/adsoyad.php', $params);
    if ($json) { echo json_encode($json, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
    echo json_encode(array('ok'=>false,'hata'=>'Baglanti hatasi')); exit;
}

// ============================================================
// 3) ADSOYADDOGUM - özel kod
// ============================================================
if ($type == 'adsoyaddogum') {
    if (!$ad || !$soyad) { echo json_encode(array('ok'=>false,'hata'=>'ad ve soyad zorunlu')); exit; }
    $q = http_build_query(array('ad'=>$ad,'soyad'=>$soyad,'dogum'=>$dogum));
    $v = cek('https://solidarksystems.alwaysdata.net/adsoyad.php?'.$q);
    if (!$v) $v = cek('https://apiv2.ajaxsystems.fun/adsoyad.php?'.$q);
    $liste = listeCikar($v);
    $cikti = array();
    foreach ($liste as $k) {
        $cins = cinsiyet($k['ad']);
        $dp = tarihParcala($k['dogum']);
        $item = temizle(array(
            'TC'=>$k['tc'],'Ad'=>$k['ad'],'Soyad'=>$k['soyad'],
            'Cinsiyet'=>($cins==='K')?'Kadin':'Erkek',
            'DogumTarihi'=>$k['dogum'],
            'Yas'=>isset($k['yas'])?$k['yas']:yasHesapla($k['dogum']),
            'Il'=>$k['il'],'Ilce'=>$k['ilce'],
            'AnneAdi'=>$k['anne_ad'],'BabaAdi'=>$k['baba_ad']
        ));
        if (!empty($item)) $cikti[] = $item;
    }
    echo json_encode(array('ok'=>true,'toplam'=>count($cikti),'sonuclar'=>$cikti,
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 4) DETAYLITC - özel kod
// ============================================================
if ($type == 'detaylitc') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli 11 haneli TC girin')); exit; }
    $kisi = null;
    $v1 = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$tc);
    if ($v1 && isset($v1['data'])) {
        foreach ($v1['data'] as $k) if (isset($k['TC']) && $k['TC']==$tc) { $kisi = kisiCikar($k); break; }
    }
    if (!$kisi) {
        $v2 = cek('https://apiv2.ajaxsystems.fun/aile.php?tc='.$tc);
        if ($v2) foreach ($v2 as $k) if (isset($k['KimlikNo']) && $k['KimlikNo']==$tc) { $kisi = kisiCikar($k); break; }
    }
    if (!$kisi) { echo json_encode(array('ok'=>false,'hata'=>'Kisi bulunamadi')); exit; }
    $cins = cinsiyet($kisi['ad']);
    $out = array(
        'ok'=>true,'tc'=>$tc,
        'kisi'=>temizle(array(
            'TC'=>$kisi['tc'],'Ad'=>$kisi['ad'],'Soyad'=>$kisi['soyad'],
            'Cinsiyet'=>($cins==='K')?'Kadin':'Erkek',
            'DogumTarihi'=>$kisi['dogum'],
            'Yas'=>isset($kisi['yas'])?$kisi['yas']:yasHesapla($kisi['dogum']),
            'Il'=>$kisi['il'],'Ilce'=>$kisi['ilce'],
            'AnneAdi'=>$kisi['anne_ad'],'BabaAdi'=>$kisi['baba_ad']
        )),
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'
    );
    echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 5) DETAYLIADRES - özel kod
// ============================================================
if ($type == 'detayliadres') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli TC girin')); exit; }
    $veri = cek('https://apiv2.ajaxsystems.fun/adres.php?tc='.$tc);
    if (!$veri || !isset($veri['success'])) { echo json_encode(array('ok'=>false,'hata'=>'Adres bulunamadi')); exit; }
    echo json_encode(array('ok'=>true,'adres'=>$veri['data'],
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 6) COCUK - özel kod
// ============================================================
if ($type == 'cocuk') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli TC')); exit; }
    $sv = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$tc);
    $av = cek('https://apiv2.ajaxsystems.fun/aile.php?tc='.$tc);
    $tum = array();
    if ($sv && isset($sv['data'])) foreach ($sv['data'] as $k) {
        $ktc = isset($k['TC'])?$k['TC']:null; if (!$ktc) continue;
        $tum[$ktc] = array('tc'=>$ktc,'ad'=>isset($k['AD'])?$k['AD']:null,'soyad'=>isset($k['SOYAD'])?$k['SOYAD']:null,
            'dogum'=>isset($k['DOGUM_YILI'])?$k['DOGUM_YILI']:null,'yas'=>isset($k['YAS'])?$k['YAS']:null,
            'il'=>isset($k['MEMLEKETIL'])?$k['MEMLEKETIL']:null,'ilce'=>isset($k['MEMLEKETILCE'])?$k['MEMLEKETILCE']:null,
            'anne_tc'=>isset($k['ANNETC'])?$k['ANNETC']:null,'baba_tc'=>isset($k['BABATC'])?$k['BABATC']:null);
    }
    if ($av && is_array($av)) foreach ($av as $k) {
        $ktc = isset($k['KimlikNo'])?$k['KimlikNo']:null; if (!$ktc || isset($tum[$ktc])) continue;
        $tum[$ktc] = array('tc'=>$ktc,'ad'=>isset($k['Isim'])?$k['Isim']:null,'soyad'=>isset($k['Soyisim'])?$k['Soyisim']:null,
            'dogum'=>isset($k['DogumTarihi'])?$k['DogumTarihi']:null,'yas'=>null,
            'il'=>isset($k['NufusIl'])?$k['NufusIl']:null,'ilce'=>isset($k['NufusIlce'])?$k['NufusIlce']:null,
            'anne_tc'=>isset($k['AnneKimlikNo'])?$k['AnneKimlikNo']:null,'baba_tc'=>isset($k['BabaKimlikNo'])?$k['BabaKimlikNo']:null);
    }
    $liste = array();
    foreach ($tum as $ktc=>$k) {
        if ($ktc === $tc) continue;
        if (($k['anne_tc'] && $k['anne_tc']===$tc) || ($k['baba_tc'] && $k['baba_tc']===$tc)) {
            $cins = cinsiyet($k['ad']);
            $liste[] = temizle(array('TC'=>$ktc,'Ad'=>$k['ad'],'Soyad'=>$k['soyad'],
                'Cinsiyet'=>($cins==='K')?'Kadin':'Erkek','Yakinlik'=>($cins==='K')?'Kizi':'Oglu',
                'DogumTarihi'=>$k['dogum'],'Yas'=>isset($k['yas'])?$k['yas']:yasHesapla($k['dogum']),
                'Il'=>$k['il'],'Ilce'=>$k['ilce']));
        }
    }
    echo json_encode(array('ok'=>true,'kok_tc'=>$tc,'toplam_cocuk'=>count($liste),'cocuklar'=>$liste,
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 7) ES - özel kod
// ============================================================
if ($type == 'es') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli TC')); exit; }
    $sv = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$tc);
    $av = cek('https://apiv2.ajaxsystems.fun/aile.php?tc='.$tc);
    $tum = array();
    if ($sv && isset($sv['data'])) foreach ($sv['data'] as $k) {
        $ktc = isset($k['TC'])?$k['TC']:null; if (!$ktc) continue;
        $tum[$ktc] = array('tc'=>$ktc,'ad'=>isset($k['AD'])?$k['AD']:null,'soyad'=>isset($k['SOYAD'])?$k['SOYAD']:null,
            'dogum'=>isset($k['DOGUM_YILI'])?$k['DOGUM_YILI']:null,'yas'=>isset($k['YAS'])?$k['YAS']:null,
            'il'=>isset($k['MEMLEKETIL'])?$k['MEMLEKETIL']:null,'ilce'=>isset($k['MEMLEKETILCE'])?$k['MEMLEKETILCE']:null,
            'anne_tc'=>isset($k['ANNETC'])?$k['ANNETC']:null,'baba_tc'=>isset($k['BABATC'])?$k['BABATC']:null);
    }
    if ($av && is_array($av)) foreach ($av as $k) {
        $ktc = isset($k['KimlikNo'])?$k['KimlikNo']:null; if (!$ktc || isset($tum[$ktc])) continue;
        $tum[$ktc] = array('tc'=>$ktc,'ad'=>isset($k['Isim'])?$k['Isim']:null,'soyad'=>isset($k['Soyisim'])?$k['Soyisim']:null,
            'dogum'=>isset($k['DogumTarihi'])?$k['DogumTarihi']:null,'yas'=>null,
            'il'=>isset($k['NufusIl'])?$k['NufusIl']:null,'ilce'=>isset($k['NufusIlce'])?$k['NufusIlce']:null,
            'anne_tc'=>isset($k['AnneKimlikNo'])?$k['AnneKimlikNo']:null,'baba_tc'=>isset($k['BabaKimlikNo'])?$k['BabaKimlikNo']:null);
    }
    $esAday = array();
    foreach ($tum as $ctc=>$c) {
        if ($ctc === $tc) continue;
        if (($c['anne_tc']===$tc || $c['baba_tc']===$tc)) {
            $diger = ($c['anne_tc']===$tc) ? $c['baba_tc'] : $c['anne_tc'];
            if ($diger && $diger !== $tc) {
                if (!isset($esAday[$diger])) $esAday[$diger] = 0;
                $esAday[$diger]++;
            }
        }
    }
    $liste = array();
    foreach ($esAday as $etc=>$cc) {
        if (!isset($tum[$etc])) continue;
        $e = $tum[$etc]; $cins = cinsiyet($e['ad']);
        $liste[] = temizle(array('TC'=>$etc,'Ad'=>$e['ad'],'Soyad'=>$e['soyad'],
            'Cinsiyet'=>($cins==='K')?'Kadin':'Erkek','Yakinlik'=>($cins==='K')?'Esi (Karisi)':'Esi (Kocasi)',
            'DogumTarihi'=>$e['dogum'],'Yas'=>isset($e['yas'])?$e['yas']:yasHesapla($e['dogum']),
            'Il'=>$e['il'],'Ilce'=>$e['ilce'],'OrtakCocukSayisi'=>$cc));
    }
    echo json_encode(array('ok'=>true,'kok_tc'=>$tc,'toplam_es'=>count($liste),'esler'=>$liste,
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 8) DOGUM - özel kod
// ============================================================
if ($type == 'dogum') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli TC')); exit; }
    $sv = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$tc);
    if (!$sv) $sv = cek('https://apiv2.ajaxsystems.fun/aile.php?tc='.$tc);
    $anneTc = null; $anneAd = null;
    if ($sv && isset($sv['data'])) foreach ($sv['data'] as $k) {
        if (isset($k['TC']) && $k['TC']==$tc) {
            $anneTc = isset($k['ANNETC'])?$k['ANNETC']:null;
            $anneAd = isset($k['ANNEADI'])?$k['ANNEADI']:null; break;
        }
    }
    if (!$anneTc) { echo json_encode(array('ok'=>false,'hata'=>'Anne bulunamadi')); exit; }
    $av = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$anneTc);
    $dtl = array();
    if ($av && isset($av['data'])) foreach ($av['data'] as $a) {
        if (isset($a['ANNETC']) && $a['ANNETC']===$anneTc) {
            if (isset($a['DOGUM_YILI']) && $a['DOGUM_YILI']) $dtl[] = $a['DOGUM_YILI'];
        }
    }
    echo json_encode(array('ok'=>true,'anne'=>array('TC'=>$anneTc,'Ad'=>$anneAd,'CocukSayisi'=>count($dtl),'CocukDogumTarihleri'=>$dtl),
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 9) SOYNESIL - özel kod (basit versiyon)
// ============================================================
if ($type == 'soynesil') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli TC')); exit; }
    $sv = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$tc);
    if (!$sv) $sv = cek('https://apiv2.ajaxsystems.fun/aile.php?tc='.$tc);
    $liste = listeCikar($sv);
    $cikti = array();
    foreach ($liste as $k) {
        if ($k['tc']===$tc) continue;
        $cikti[] = temizle(array('TC'=>$k['tc'],'Ad'=>$k['ad'],'Soyad'=>$k['soyad'],
            'DogumTarihi'=>$k['dogum'],'Yas'=>isset($k['yas'])?$k['yas']:yasHesapla($k['dogum']),
            'Il'=>$k['il'],'Ilce'=>$k['ilce'],'AnneAdi'=>$k['anne_ad'],'BabaAdi'=>$k['baba_ad']));
    }
    echo json_encode(array('ok'=>true,'toplam'=>count($cikti),'yakinlar'=>$cikti,
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 10) KUZEN - özel kod (basit versiyon)
// ============================================================
if ($type == 'kuzen') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli TC')); exit; }
    $sv = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$tc);
    if (!$sv || !isset($sv['data'])) { echo json_encode(array('ok'=>false,'hata'=>'Kisi bulunamadi')); exit; }
    $kok = null;
    foreach ($sv['data'] as $k) if (isset($k['TC']) && $k['TC']==$tc) { $kok = $k; break; }
    if (!$kok) { echo json_encode(array('ok'=>false,'hata'=>'Kisi bulunamadi')); exit; }
    $kuzenler = array();
    // Kuzen: amca/hala/dayı/teyzenin çocukları - basitleştirilmiş
    foreach ($sv['data'] as $k) {
        if (!isset($k['TC']) || $k['TC']==$tc) continue;
        // Aynı soyadlı farklı aile üyeleri kuzen olabilir (basit tahmin)
        $kuzenler[$k['TC']] = $k;
    }
    $liste = array();
    foreach ($kuzenler as $ktc=>$k) {
        $liste[] = temizle(array('TC'=>$ktc,'Ad'=>isset($k['AD'])?$k['AD']:null,
            'Soyad'=>isset($k['SOYAD'])?$k['SOYAD']:null,
            'DogumTarihi'=>isset($k['DOGUM_YILI'])?$k['DOGUM_YILI']:null,
            'Yas'=>isset($k['YAS'])?$k['YAS']:null,
            'Il'=>isset($k['MEMLEKETIL'])?$k['MEMLEKETIL']:null,
            'Ilce'=>isset($k['MEMLEKETILCE'])?$k['MEMLEKETILCE']:null));
    }
    echo json_encode(array('ok'=>true,'kok_tc'=>$tc,'toplam_kuzen'=>count($liste),'kuzenler'=>$liste,
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 11) GSMSULALE - özel kod
// ============================================================
if ($type == 'gsmsulale') {
    if (strlen($gsm)===12 && substr($gsm,0,2)==='90') $gsm = substr($gsm,2);
    if (strlen($gsm)===11 && substr($gsm,0,1)==='0') $gsm = substr($gsm,1);
    if (strlen($gsm)!==10 || substr($gsm,0,1)!=='5') { echo json_encode(array('ok'=>false,'hata'=>'Gecerli GSM girin')); exit; }
    $gv = cek('https://apiv2.ajaxsystems.fun/gsmtc.php?gsm='.$gsm);
    $gtc = null;
    if ($gv && is_array($gv)) {
        if (isset($gv['TC'])) $gtc = $gv['TC'];
        elseif (isset($gv['KimlikNo'])) $gtc = $gv['KimlikNo'];
        elseif (isset($gv[0]['TC'])) $gtc = $gv[0]['TC'];
    }
    if (!$gtc) { echo json_encode(array('ok'=>false,'hata'=>'GSM TC bulunamadi')); exit; }
    $sv = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$gtc);
    $liste = listeCikar($sv);
    $cikti = array();
    foreach ($liste as $k) {
        $cikti[] = temizle(array('TC'=>$k['tc'],'Ad'=>$k['ad'],'Soyad'=>$k['soyad'],
            'DogumTarihi'=>$k['dogum'],'Yas'=>isset($k['yas'])?$k['yas']:yasHesapla($k['dogum']),
            'Il'=>$k['il'],'Ilce'=>$k['ilce']));
    }
    echo json_encode(array('ok'=>true,'gsm'=>$gsm,'kok_tc'=>$gtc,'toplam'=>count($cikti),'liste'=>$cikti,
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// 12) SULALEGSM - özel kod
// ============================================================
if ($type == 'sulalegsm') {
    if (strlen($tc) !== 11) { echo json_encode(array('ok'=>false,'hata'=>'Gecerli TC')); exit; }
    $sv = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc='.$tc);
    $liste = listeCikar($sv);
    $gsmler = array();
    $sayac = 0;
    foreach ($liste as $k) {
        if ($sayac >= 10) break;
        $sayac++;
        $gv = cek('https://apiv2.ajaxsystems.fun/tcgsm.php?tc='.$k['tc'], 5);
        if (!$gv) continue;
        array_walk_recursive($gv, function($v, $key) use (&$gsmler, $k) {
            if (is_string($v) || is_numeric($v)) {
                $g = preg_replace('/[^0-9]/', '', (string)$v);
                if (strlen($g)===12 && substr($g,0,2)==='90') $g = substr($g,2);
                if (strlen($g)===11 && substr($g,0,1)==='0') $g = substr($g,1);
                if (strlen($g)===10 && substr($g,0,1)==='5') {
                    $gsmler[$g] = isset($k['tc'])?$k['tc']:null;
                }
            }
        });
    }
    $cikti = array();
    foreach ($gsmler as $g=>$t) $cikti[] = array('GSM'=>$g,'TC'=>$t);
    echo json_encode(array('ok'=>true,'kok_tc'=>$tc,'toplam'=>count($cikti),'liste'=>$cikti,
        'script_sahibi'=>'@fbxnext','instagram'=>'@logsuzlarpanel','tiktok'=>'@logsuzlar.inc'),
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}

// ============================================================
// BULUNAMADI
// ============================================================
echo json_encode(array('ok'=>false,'hata'=>'Bilinmeyen type: '.$type,
    'mevcut'=>array('tc','tcpro','tapu','adres','eokul','isyeri','sgk','aile','ailepro',
        'sulale','tcgsm','gsmtc','adaparsel','adsoyad','adsoyaddogum','detaylitc','detayliadres',
        'cocuk','es','dogum','soynesil','kuzen','gsmsulale','sulalegsm')),
    JSON_UNESCAPED_UNICODE);
