<?php
$ncore = $_POST['ncore'];
$split = $_POST['split'];
$nremote = $_POST['nremote'];
$taper = $_POST['taper'];
$nintl = $_POST['nintl'];
$freq = $_POST['freq'];
$nsb = $_POST['nsb'];
$sbwidth = $_POST['sbwidth'];
$nchan = $_POST['nchan'];
$time = $_POST['time'];
$debug = $_POST['debug'];
?>

<html>
<head>
<title>NenuFAR Image noise calculator (beta)</title>
<link href="sensn.css" rel="stylesheet" type="text/css">
</head>

<!--
NenuFAR Image calculator written by M. Pommier for NenuFAR  (April 2019) 
adapted from Image calculator v0.2, 5 July 2012 by George Heald (ASTRON)
-->

<!--
Form skeleton and css ideas from
http://24ways.org/2009/have-a-field-day-with-html5-forms/
-->

<body>

<h2>NenuFAR Image noise calculator</h2>

<p>This calculator is in beta, so please use it with caution. It uses theoretical SEFD values computed from Condon 2002, but these will be updated soon with empirical numbers. For information about the array and its capabilities please see the <a href="https://nenufar.obs-nancay.fr/en/astronomer/">NenuFAR webpage at Paris Observatory</a>.</p>
<p>The calculations performed by this tool follow <a href="http://www.skatelescope.org/uploaded/59513_113_Memo_Nijboer.pdf">SKA Memo 113</a> by Roland Nijboer, Mamta Pandey-Pommier, &amp; Ger de Bruyn.</p>

<?php
if (!isset($_POST['submit'])){
?>
<form method=post action="<?php echo $_SERVER['PHP_SELF'];?>" id=calculator>
 <fieldset>
  <legend>Observation details</legend>
  <ul id=calclist>
   <li>
    <label for=ncore>Number of core stations (max 96)</label>
    <input id=ncore name=ncore type=number value=96 min=0 max=96 required autofocus>
    <ul><li>
    <label for=split>Split core stations?</label>
    <input id=split name=split type=checkbox value=True>
    </li></ul>
   </li>
   <li>
    <label for=nremote>Number of remote stations (max 6)</label>
    <input id=nremote name=nremote type=number value=5 min=0 max=6 required>
    <ul><li>
    <label for=taper>Taper remote stations to 6 stations?</label>
    <input id=taper name=taper type=checkbox value=True>
    </li></ul>
   </li>
   <li>
    <label for=nintl>Number of international stations (max 8)</label>
    <input id=nintl name=nintl type=number value=9 min=0 max=12 required>
   </li>
   <li>
    <label for=maxbl>Maximum baseline length (km)</label>
    <input id=maxbl name=maxbl type=number value=3 min=0.1 step=0.1 max=3 required>
   </li>
   <li>
    <label for=freq>Observing frequency</label>
    <select id=freq name=freq>
     <option value="15">15 MHz </option>
     <option value="30">30 MHz </option>
     <option value="45" selected>45 MHz</option>
     <option value="60">60 MHz </option>
     <option value="75">75 MHz </option>
    </select>
   </li>
   <li>
    <label for=nsb>Number of subbands</label>
    <input id=nsb name=nsb type=number step=1 value=1 min=1 max=488 required>
   </li>
   <li>
    <label for=sbwidth>Subband width</label>
    <select id=sbwidth name=sbwidth>
     <option value="195312.00" selected>195.3125 kHz (? MHz clock)</option>
     <option value="3000000.00" selected>3000 kHz (? MHz clock)</option>
    </select>
    <ul><li>
     <label for=nchan>Number of channels</label>
     <select id=nchan name=nchan>
      <option value="1">1 (use full bandwidth)</option>
      <option value="2">2</option>
      <option value="4">4</option>
      <option value="8">8</option>
      <option value="16">16</option>
      <option value="32">32</option>
      <option value="64" selected>64 (normal usage)</option>
      <option value="128">128</option>
      <option value="256">256 (max recommended)</option>
     </select>
    </li></ul>
   </li>
   <li>
    <label for=time>Time (sec)</label>
    <input id=time name=time type=number value=3600 min=1 max=100000 required>
   </li>
  </ul>
 </fieldset>
 <fieldset>
  <label for=debug>Debug mode?</label>
  <input id=debug name=debug type=checkbox value=True>
 </fieldset>
 <fieldset>
  <button type=submit value=submit name=submit>Calculate</button>
 </fieldset>
</form>

<?php
} else {
 if ($freq>100 && $split=="True") {
  $nspl = $ncore * 2;
  echo "<p><font color=green><b>Using splitted HBA core stations, so that is ".$ncore."x2=".$nspl."</b></font></p>";
  $ncore = $nspl;
 }
 if ($freq>100 && $taper=="True") {
  echo "<p><font color=green><b>Using tapered HBA remote stations, so the calculator treats this as ".$ncore."+".$nremote." core stations and 0 remote stations</b></font></p>";
  $ncore = $ncore + $nremote;
  $nremote = 0;
 }
 $totalst = $ncore + $nremote + $nintl;
 if ($totalst > 102) {
  echo "<p><font color=red><b>Warning: Total number of stations (".$totalst.") exceeds maximum number that can be correlated (102).</b></font></p>";
 }
 $bandwidth = $nsb * $sbwidth / 1.e6;
 $sbwidthkhz = $sbwidth / 1.e3;
 $chwidth = $sbwidth / $nchan;
 $chwidthkhz = $chwidth / 1.e3;
 if ($freq>100) {
  $array = "HBA";
 } else if (substr($freq,-1)=="I") {
  $array = "LBA_INNER";
  $freq = substr($freq,0,-1);
 } else {
  $array = "LBA_OUTER";
 }
 echo "<p>Your inputs:";
 echo "<ul><li>Number of core stations = ".$ncore;
 echo "<li>Number of remote stations = ".$nremote;
 echo "<li>Number of international stations = ".$nintl;
 $nccbl = ($ncore * ($ncore - 1)) / 2;
 $nrrbl = ($nremote * ($nremote - 1)) / 2;
 $niibl = ($nintl * ($nintl - 1)) / 2;
 $ncrbl = ($ncore * $nremote);
 $ncibl = ($ncore * $nintl);
 $nribl = ($nremote * $nintl);
 $totalbl = $nccbl + $nrrbl + $niibl + $ncrbl + $ncibl + $nribl;
 echo "<li>Frequency = ".$freq." MHz (".$array.")";
 $timemin = $time / 60;
 $timehr = $timemin / 60;
 echo "<li>Time = ".$time." sec = ".$timemin." min = ".$timehr." hr";
 echo "<li>Bandwidth = ".$nsb." x ".$sbwidthkhz." kHz = ".$bandwidth." MHz";
 echo "<li>Number of channels = ".$nchan." (channel width = ".$chwidthkhz." kHz)</ul>";
 echo "Extra information:<ul>";
 echo "<li>Two polarizations are assumed.";
 echo "<li>An image weight parameter was not included but <i>may increase the calculated values</i> by a factor of 1.3-2.";
 echo "<li>No bandwidth losses due to RFI were assumed. This may be unrealistic. Typical band edges are excluded (amounting to keeping 59/64 channels). This is implicit in the subband bandwidths used here.</ul>";
 $sefdcore = array(
  "15" => "25273",
  "30" => "1234",
  "45" => "835",
  "60" => "803",
  "75" => "746",
 );
 $sefdremote = array(
  "15" => "23787",
  "30" => "1161",
  "45" => "786",
  "60" => "756",
  "75" => "702",
 );
 $sefdintl = array(
  "15I" => "518740",
  "30I" => "40820",
  "45I" => "18840",
  "60I" => "14760",
  "75I" => "24660",
  "15" => "518740",
  "30" => "40820",
  "45" => "18840",
  "60" => "14760",
  "75" => "24660",
 );
 $prodcc = $sefdcore[$freq];
 $prodrr = $sefdremote[$freq];
 $prodii = $sefdintl[$freq];
 $prodcr = sqrt($sefdcore[$freq])*sqrt($sefdremote[$freq]);
 $prodci = sqrt($sefdcore[$freq])*sqrt($sefdintl[$freq]);
 $prodri = sqrt($sefdremote[$freq])*sqrt($sefdintl[$freq]);
 if ($debug=="True") {
  echo "<p>Debug info:<ul>";
  echo "<li>Number of core-core baselines = ".$nccbl." with effective SEFD ".$prodcc." Jy";
  echo "<li>Number of remote-remote baselines = ".$nrrbl." with effective SEFD ".$prodrr." Jy";
  echo "<li>Number of intl-intl baselines = ".$niibl." with effective SEFD ".$prodii." Jy";
  echo "<li>Number of core-remote baselines = ".$ncrbl." with effective SEFD ".$prodcr." Jy";
  echo "<li>Number of core-intl baselines = ".$ncibl." with effective SEFD ".$prodci." Jy";
  echo "<li>Number of remote-intl baselines = ".$nribl." with effective SEFD ".$prodri." Jy";
  echo "<li>Total number of baselines = ".$totalbl."</ul></p>";
 }
 //$cc = $prodcc / sqrt($nccbl*2);
 //$rr = $prodrr / sqrt($nrrbl*2);
 //$ii = $prodii / sqrt($niibl*2);
 //$cr = $prodcr / sqrt($ncrbl*2);
 //$ci = $prodci / sqrt($ncibl*2);
 //$ri = $prodri / sqrt($nribl*2);
 //$sensall = sqrt(pow($cc,2)+pow($rr,2)+pow($ii,2)+pow($cr,2)+pow($ci,2)+pow($ri,2));
 //$imsens = $sensall / sqrt($bandwidth*$time*1.e6);
 $imsensc = $prodcc/sqrt($bandwidth*$time*1.e6);
 $imsenscr = $prodrr/sqrt($bandwidth*$time*1.e6);
 $imsensbcr = 1/sqrt(4*$bandwidth*$time*1.e6*($nccbl/pow($prodcc,2)+$nrrbl/pow($prodrr,2)+$niibl/pow($prodii,2)+$ncrbl/pow($prodcr,2)+$ncibl/pow($prodci,2)+$nribl/pow($prodri,2)));
 $chsens = 1/sqrt(4*$chwidth*$time*($nccbl/pow($prodcc,2)+$nrrbl/pow($prodrr,2)+$niibl/pow($prodii,2)+$ncrbl/pow($prodcr,2)+$ncibl/pow($prodci,2)+$nribl/pow($prodri,2)));
 $imsensmjyc = 1000*$imsensc;
 $imsensmjycr = 1000*$imsenscr;
 $imsensmjybcr = 1000*$imsensbcr;
 $chsensmjy = 1000*$chsens;
 $imsensujyc = 1000000*$imsensc;
 $imsensujycr = 1000000*$imsenscr;
 $imsensujybcr = 1000000*$imsensbcr;
 $chsensujy = 1000000*$chsens;
 $imsensstrc = sprintf("%.2f",$imsensc);
 $imsensstrcr = sprintf("%.2f",$imsenscr);
 $imsensstrbcr = sprintf("%.2f",$imsensbcr);
 $chsensstr = sprintf("%.2f",$chsens);
 $imsensmjystrc = sprintf("%.2f",$imsensmjyc);
 $imsensmjystrcr = sprintf("%.2f",$imsensmjycr);
 $imsensmjystrbcr = sprintf("%.2f",$imsensmjybcr);
 $chsensmjystr = sprintf("%.2f",$chsensmjy);
 $imsensujystrc = sprintf("%.2f",$imsensujyc);
 $imsensujystrcr = sprintf("%.2f",$imsensujycr);
 $imsensujystrbcr = sprintf("%.2f",$imsensujybcr);
 $chsensujystr = sprintf("%.2f",$chsensujy);
 if ($imsensc > 1) {
  echo "<p><font color=green><b>Image sensitivity core stations= ".$imsensstrc." Jy/beam</b></font></p>";
 } else if ($imsensmjy > 1) {
  echo "<p><font color=green><b>Image sensitivity core stations= ".$imsensujystrc." &mu;Jy/beam</b></font></p>";
 } else {
  echo "<p><font color=green><b>Image sensitivity core stations= ".$imsensmjystrc." mJy/beam</b></font></p>";
 }
 
 if ($nremote != 0) {
  if ($imsenscr > 1) {
   echo "<p><font color=green><b>Image sensitivity core+remote stations= ".$imsensstrcr." Jy/beam</b></font></p>";
  } else if ($imsensmjy > 1) {
   echo "<p><font color=green><b>Image sensitivity core+remote stations= ".$imsensujystrcr." &mu;Jy/beam</b></font></p>";
  } else {
   echo "<p><font color=green><b>Image sensitivity core+remote stations= ".$imsensmjystrcr." mJy/beam</b></font></p>";
  }
 }

 if ($imsensbcr > 1) {
  echo "<p><font color=green><b>Image sensitivity including baselines & core+remote stations= ".$imsensstrbcr." Jy/beam</b></font></p>";
 } else if ($imsensmjy > 1) {
  echo "<p><font color=green><b>Image sensitivity including baselines & core+remote stations= ".$imsensujystrbcr." &mu;Jy/beam</b></font></p>";
 } else {
  echo "<p><font color=green><b>Image sensitivity including baselines & core+remote(if > 0) stations= ".$imsensmjystrbcr." mJy/beam</b></font></p>";
 }
 if ($chsens > 1) {
  echo "<p><font color=green><b>Sensitivity per channel = ".$chsensstr." Jy/beam</b></font></p>";
 } else if ($chsensmjy > 1) {
  echo "<p><font color=green><b>Sensitivity per channel = ".$chsensmjystr." mJy/beam</b></font></p>";
 } else {
  echo "<p><font color=green><b>Sensitivity per channel = ".$chsensujystr." &mu;Jy/beam</b></font></p>";
 }
 echo "<p>Use your browser's &quot;Back&quot; button to change input settings.</p>";
}
?>

<p id=logos><img src="nenufar.png" height=75px>&nbsp;&nbsp;<img src="usn.png" height=75px></p>
<p id=credit><i>Written for NenuFAR by Mamta Pommier (April 2019) (adapted from LOFAR sensitivity calculator by George Heald, v0.2 (5 July 2012)).
 For additional information about the NenuFAR sensitivity calculator described on this page please e-mail: mamtapan@gmail.com </i></p>
<div id=lofar><img src="nenufarant.png" width=280px></p>

</body>
</html>
