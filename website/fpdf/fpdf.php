<?php
// FPDF - Free PDF generation class for PHP
// Based on FPDF 1.86 by Olivier PLATHEY - http://www.fpdf.org
// Redistributed here for bundled use in QuizCert Pro

define('FPDF_VERSION','1.86');

class FPDF {
    protected $page=0;
    protected $n=0;
    protected $offsets=[];
    protected $buffer='';
    protected $pages=[];
    protected $state=0;
    protected $compress;
    protected $k;
    protected $DefOrientation;
    protected $CurOrientation;
    protected $StdPageSizes=['a3'=>[841.89,1190.55],'a4'=>[595.28,841.89],'a5'=>[420.94,595.28],'letter'=>[612,792],'legal'=>[612,1008]];
    protected $DefPageSize;
    protected $CurPageSize;
    protected $CurRotation;
    protected $PageInfo=[];
    protected $wPt,$hPt;
    protected $w,$h;
    protected $lMargin;
    protected $tMargin;
    protected $rMargin;
    protected $cMargin;
    protected $x,$y;
    protected $lasth;
    protected $LineWidth;
    protected $CoreFonts=['courier'=>true,'helvetica'=>true,'arial'=>true,'times'=>true,'symbol'=>true,'zapfdingbats'=>true];
    protected $fonts=[];
    protected $FontFiles=[];
    protected $encodings=[];
    protected $cmaps=[];
    protected $FontFamily='';
    protected $FontStyle='';
    protected $underline=false;
    protected $CurrentFont;
    protected $FontSizePt=12;
    protected $FontSize;
    protected $DrawColor='0 G';
    protected $FillColor='0 g';
    protected $TextColor='0 g';
    protected $ColorFlag=false;
    protected $WithAlpha=false;
    protected $ws=0;
    protected $images=[];
    protected $PageLinks;
    protected $links=[];
    protected $AutoPageBreak;
    protected $PageBreakTrigger;
    protected $InHeader=false;
    protected $InFooter=false;
    protected $AliasNbPages;
    protected $ZoomMode;
    protected $LayoutMode;
    protected $metadata=[];
    protected $PDFVersion;

    function __construct($orientation='P',$unit='mm',$size='A4') {
        if(get_magic_quotes_runtime()) @set_magic_quotes_runtime(0);
        $this->buffer=''; $this->pages=[]; $this->PageInfo=[]; $this->fonts=[];
        $this->images=[]; $this->links=[]; $this->n=0; $this->PDFVersion='1.3';
        $margin=28.35/$this->_getscalefactor($unit);
        $this->k=$this->_getscalefactor($unit);
        $this->SetMargins($margin,$margin);
        $this->cMargin=$margin/10;
        $this->LineWidth=.567/$this->k;
        $this->SetAutoPageBreak(true,$this->bMargin??2*$margin);
        $size=mb_strtolower($size);
        if(isset($this->StdPageSizes[$size])) $size=$this->StdPageSizes[$size];
        else $size=[$size[0]*$this->k, $size[1]*$this->k];
        $this->DefPageSize=$size;
        $orientation=strtolower($orientation[0]);
        if($orientation=='p') {
            $this->DefOrientation='P'; $this->w=$size[0]/$this->k; $this->h=$size[1]/$this->k;
        } else {
            $this->DefOrientation='L'; $this->w=$size[1]/$this->k; $this->h=$size[0]/$this->k;
        }
        $this->wPt=$this->w*$this->k; $this->hPt=$this->h*$this->k;
        $this->CurOrientation=$this->DefOrientation; $this->CurPageSize=$this->DefPageSize;
        $this->CurRotation=0; $this->compress=function_exists('gzcompress');
        $this->ZoomMode='default'; $this->LayoutMode='default';
        $this->metadata=['Producer'=>'FPDF '.FPDF_VERSION];
        $this->AliasNbPages='';
        $this->lasth=0; $this->state=0;
    }

    protected function _getscalefactor($unit) {
        switch($unit) {
            case 'pt': return 1;
            case 'mm': return 72/25.4;
            case 'cm': return 72/2.54;
            case 'in': return 72;
            default: $this->Error('Incorrect unit: '.$unit);
        }
    }

    function SetMargins($left,$top,$right=-1) {
        $this->lMargin=$left;
        $this->tMargin=$top;
        $this->rMargin=$right<0?$left:$right;
        if(!isset($this->bMargin)) $this->bMargin=2*$top;
    }
    function SetLeftMargin($margin){$this->lMargin=$margin;if($this->page>0&&$this->x<$margin)$this->x=$margin;}
    function SetTopMargin($margin){$this->tMargin=$margin;}
    function SetRightMargin($margin){$this->rMargin=$margin;}
    function SetAutoPageBreak($auto,$margin=0){$this->AutoPageBreak=$auto;$this->bMargin=$margin;$this->PageBreakTrigger=$this->h-$margin;}
    function SetDisplayMode($zoom,$layout='default'){$this->ZoomMode=$zoom;$this->LayoutMode=$layout;}
    function SetCompression($compress){$this->compress=$compress;}
    function SetTitle($title,$isUTF8=false){$this->metadata['Title']=$isUTF8?$title:utf8_encode($title);}
    function SetAuthor($author,$isUTF8=false){$this->metadata['Author']=$isUTF8?$author:utf8_encode($author);}
    function SetSubject($subject,$isUTF8=false){$this->metadata['Subject']=$isUTF8?$subject:utf8_encode($subject);}
    function SetKeywords($keywords,$isUTF8=false){$this->metadata['Keywords']=$isUTF8?$keywords:utf8_encode($keywords);}
    function SetCreator($creator,$isUTF8=false){$this->metadata['Creator']=$isUTF8?$creator:utf8_encode($creator);}
    function AliasNbPages($alias='{nb}'){$this->AliasNbPages=$alias;}
    function Error($msg){throw new RuntimeException('FPDF error: '.$msg);}

    function Open(){$this->state=1;}

    function Close(){
        if($this->state==3)return;
        if($this->page==0)$this->AddPage();
        $this->InFooter=true;$this->Footer();$this->InFooter=false;
        $this->_endpage();$this->_enddoc();
    }

    function AddPage($orientation='',$size='',$rotation=0){
        if($this->state==3)$this->Error('The document is closed');
        $family=$this->FontFamily;
        $style=$this->FontStyle.($this->underline?'U':'');
        $fontsize=$this->FontSizePt;
        $lw=$this->LineWidth;$dc=$this->DrawColor;$fc=$this->FillColor;$tc=$this->TextColor;$cf=$this->ColorFlag;
        if($this->page>0){$this->InFooter=true;$this->Footer();$this->InFooter=false;$this->_endpage();}
        $this->_beginpage($orientation,$size,$rotation);
        $this->_out('2 J');$this->LineWidth=$lw;$this->_out(sprintf('%.2F w',$lw*$this->k));
        if($family)$this->SetFont($family,$style,$fontsize);
        if($this->DrawColor!='0 G'){$this->DrawColor=$dc;$this->_out($dc);}
        if($this->FillColor!='0 g'){$this->FillColor=$fc;$this->_out($fc);}
        $this->TextColor=$tc;$this->ColorFlag=$cf;
        $this->InHeader=true;$this->Header();$this->InHeader=false;
        if($this->lasth==0)$this->lasth=$this->FontSize*$this->FontFactor??1;
    }

    function Header(){}
    function Footer(){}
    function PageNo(){return $this->page;}

    function SetDrawColor($r,$g=-1,$b=-1){
        if(($r==0&&$g==0&&$b==0)||$g==-1)$this->DrawColor=sprintf('%.3F G',$r/255);
        else $this->DrawColor=sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255);
        if($this->page>0)$this->_out($this->DrawColor);
    }
    function SetFillColor($r,$g=-1,$b=-1){
        if(($r==0&&$g==0&&$b==0)||$g==-1)$this->FillColor=sprintf('%.3F g',$r/255);
        else $this->FillColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
        $this->ColorFlag=($this->FillColor!=$this->TextColor);
        if($this->page>0)$this->_out($this->FillColor);
    }
    function SetTextColor($r,$g=-1,$b=-1){
        if(($r==0&&$g==0&&$b==0)||$g==-1)$this->TextColor=sprintf('%.3F g',$r/255);
        else $this->TextColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
        $this->ColorFlag=($this->FillColor!=$this->TextColor);
    }
    function GetStringWidth($s){
        if($this->CurrentFont===null)$this->Error('No font has been set');
        $s=(string)$s;$l=0;$cw=$this->CurrentFont['cw'];
        $nb=strlen($s);
        for($i=0;$i<$nb;$i++)$l+=isset($cw[$s[$i]])?$cw[$s[$i]]:600;
        return $l*$this->FontSize/1000;
    }
    function SetLineWidth($width){$this->LineWidth=$width;if($this->page>0)$this->_out(sprintf('%.2F w',$width*$this->k));}
    function Line($x1,$y1,$x2,$y2){$this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',$x1*$this->k,($this->h-$y1)*$this->k,$x2*$this->k,($this->h-$y2)*$this->k));}
    function Rect($x,$y,$w,$h,$style=''){
        if($style=='F')$op='f'; elseif($style=='FD'||$style=='DF')$op='B'; else $op='S';
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s',$x*$this->k,($this->h-$y)*$this->k,$w*$this->k,-$h*$this->k,$op));
    }

    function AddFont($family,$style='',$file=''){$this->Error('AddFont not supported in this build. Use core fonts.');}

    function SetFont($family,$style='',$size=0){
        $family=strtolower($family);
        if($family=='arial')$family='helvetica';
        $style=strtoupper($style);
        if(strpos($style,'U')!==false){$this->underline=true;$style=str_replace('U','',$style);}
        else $this->underline=false;
        if($style=='IB')$style='BI';
        if($size==0)$size=$this->FontSizePt;
        if($this->FontFamily==$family&&$this->FontStyle==$style&&$this->FontSizePt==$size)return;
        $fontkey=$family.$style;
        if(!isset($this->fonts[$fontkey])){
            if(!isset($this->CoreFonts[$family]))$this->Error('Undefined font: '.$family.' '.$style);
            $this->fonts[$fontkey]=['i'=>count($this->fonts)+1,'type'=>'Core','name'=>$this->_coreFontName($family,$style),'cw'=>$this->_getCoreWidths($family,$style)];
        }
        $this->FontFamily=$family;$this->FontStyle=$style;$this->FontSizePt=$size;
        $this->FontSize=$size/$this->k;$this->CurrentFont=&$this->fonts[$fontkey];
        $this->FontFactor=$this->k;
        if($this->page>0)$this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$size));
    }

    protected function _coreFontName($family,$style){
        $map=['helvetica'=>'Helvetica','courier'=>'Courier','times'=>'Times','symbol'=>'Symbol','zapfdingbats'=>'ZapfDingbats'];
        $name=$map[$family]??ucfirst($family);
        if($family!='symbol'&&$family!='zapfdingbats'){
            if($style=='B')$name.='-Bold';
            elseif($style=='I')$name.=($family=='times'?'-Italic':'-Oblique');
            elseif($style=='BI')$name.=($family=='times'?'-BoldItalic':'-BoldOblique');
        }
        return $name;
    }

    protected function _getCoreWidths($family,$style){
        // Standard widths for Helvetica (approximate for all core fonts)
        $w=[' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,"'"=>191,
            '('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,
            '0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,
            '8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,
            '@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,
            'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,
            'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,
            'X'=>667,'Y'=>667,'Z'=>611,'['=>278,'\\'=>278,']'=>278,'^'=>469,'_'=>556,
            '`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,
            'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,
            'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,
            'x'=>500,'y'=>500,'z'=>500,'{'=>334,'|'=>260,'}'=>334,'~'=>584];
        return $w;
    }

    function SetFontSize($size){
        if($this->FontSizePt==$size)return;
        $this->FontSizePt=$size;$this->FontSize=$size/$this->k;
        if($this->page>0&&$this->CurrentFont)$this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$size));
    }
    function AddLink(){$n=count($this->links)+1;$this->links[$n]=[0,0];return $n;}
    function SetLink($link,$y=0,$page=-1){if($y==-1)$y=$this->y;if($page==-1)$page=$this->page;$this->links[$link]=[$page,$y];}
    function GetX(){return $this->x;}
    function SetX($x){$this->x=$x>=0?$x:$this->w+$x;}
    function GetY(){return $this->y;}
    function SetY($y,$resetX=true){if($y>=0)$this->y=$y;else $this->y=$this->h+$y;if($resetX)$this->x=$this->lMargin;}
    function SetXY($x,$y){$this->SetX($x);$this->SetY($y,false);}

    function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=false,$link=''){
        $k=$this->k;
        if($this->y+$h>$this->PageBreakTrigger&&!$this->InHeader&&!$this->InFooter&&$this->AutoPageBreak){$x=$this->x;$ws=$this->ws;if($ws>0){$this->ws=0;$this->_out('0 Tw');}$this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);$this->x=$x;if($ws>0){$this->ws=$ws;$this->_out(sprintf('%.3F Tw',$ws*$k));}}
        if($w==0)$w=$this->w-$this->rMargin-$this->x;
        $s='';
        if($fill||$border==1){
            if($fill)$s=$border==1?'B ':($this->DrawColor&&$this->FillColor!=$this->DrawColor?'B ':'f ');
            else $s='S ';
            $s=sprintf('%.2F %.2F %.2F %.2F re %s',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$s);
        }
        if(is_string($border)){
            $x=$this->x;$y=$this->y;
            if(strpos($border,'L')!==false)$s.=sprintf('%.2F %.2F m %.2F %.2F l S',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
            if(strpos($border,'T')!==false)$s.=sprintf('%.2F %.2F m %.2F %.2F l S',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
            if(strpos($border,'R')!==false)$s.=sprintf('%.2F %.2F m %.2F %.2F l S',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
            if(strpos($border,'B')!==false)$s.=sprintf('%.2F %.2F m %.2F %.2F l S',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
        }
        if($txt!==''){
            if($this->CurrentFont===null)$this->Error('No font has been set');
            if($this->ColorFlag&&$this->page>0)$s.='q '.$this->TextColor.' ';
            if($align=='R')$dx=$w-$this->cMargin-$this->GetStringWidth($txt);
            elseif($align=='C')$dx=($w-$this->GetStringWidth($txt))/2;
            else $dx=$this->cMargin;
            if($this->ColorFlag)$s.="q $this->TextColor ";
            $txt2=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
            $s.=sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
            if($this->underline)$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
            if($this->ColorFlag)$s.=' Q';
        }
        if($s)$this->_out($s);
        $this->lasth=$h;
        if($ln>0){$this->y+=$h;if($ln==1)$this->x=$this->lMargin;}
        else $this->x+=$w;
    }

    function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=false){
        if($this->CurrentFont===null)$this->Error('No font has been set');
        $cw=$this->CurrentFont['cw'];
        if($w==0)$w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',(string)$txt);
        $nb=strlen($s);
        if($nb>0&&$s[$nb-1]=="\n")$nb--;
        $b=0;if($border){if($border==1){$border='LTRB';$b='LRT';$b2='LR';}else{$b2='';if(strpos($border,'L')!==false)$b2.='L';if(strpos($border,'R')!==false)$b2.='R';$b=strpos($border,'T')!==false?$b2.'T':$b2;}}
        $sep=-1;$i=0;$j=0;$l=0;$ns=0;$nl=1;
        while($i<$nb){
            $c=$s[$i];
            if($c=="\n"){if($this->ws>0){$this->ws=0;$this->_out('0 Tw');}$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);$i++;$sep=-1;$j=$i;$l=0;$ns=0;$nl++;if($border&&$nl==2)$b=$b2;continue;}
            if($c==' '){$sep=$i;$ls=$l;$ns++;}
            $l+=isset($cw[$c])?$cw[$c]:600;
            if($l>$wmax){
                if($sep==-1){if($i==$j)$i++;if($this->ws>0){$this->ws=0;$this->_out('0 Tw');}$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);}
                else{if($align=='J'){$this->ws=($ns>1?(($wmax-$ls)/1000*$this->FontSize/($ns-1)):0);$this->_out(sprintf('%.3F Tw',$this->ws*$this->k));}$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);$i=$sep+1;}
                $sep=-1;$j=$i;$l=0;$ns=0;$nl++;if($border&&$nl==2)$b=$b2;}
            else $i++;
        }
        if($this->ws>0){$this->ws=0;$this->_out('0 Tw');}
        if($border&&strpos($border,'B')!==false)$b.='B';
        $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
        $this->x=$this->lMargin;
    }

    function Ln($h=null){
        if($this->CurrentFont===null&&$h===null)$this->Error('No font has been set');
        $this->x=$this->lMargin;
        $this->y+=$h!==null?$h:$this->lasth;
    }

    function Image($file,$x=null,$y=null,$w=0,$h=0,$type='',$link=''){
        if(!isset($this->images[$file])){
            if($type==''){$pos=strrpos($file,'.');if(!$pos)$this->Error('Image file has no extension and no type: '.$file);$type=substr($file,$pos+1);}
            $type=strtolower($type);
            if($type=='jpeg')$type='jpg';
            $mtd='_parse'.$type;
            if(!method_exists($this,$mtd))$this->Error('Unsupported image type: '.$type);
            $info=$this->$mtd($file);
            $info['i']=count($this->images)+1;
            $this->images[$file]=$info;
        } else $info=$this->images[$file];
        if($w==0&&$h==0){$w=$info['w']/$this->k;$h=$info['h']/$this->k;}
        elseif($w==0)$w=$h*$info['w']/$info['h'];
        elseif($h==0)$h=$w*$info['h']/$info['w'];
        if($x===null)$x=$this->x;
        if($y===null){if($this->y+$h>$this->PageBreakTrigger&&!$this->InHeader&&!$this->InFooter&&$this->AutoPageBreak){$this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);} $y=$this->y;}
        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
    }

    protected function _parsejpg($file){
        $a=getimagesize($file);
        if(!$a)$this->Error('Missing or incorrect image file: '.$file);
        if($a[2]!=2)$this->Error('Not a JPEG file: '.$file);
        $channels=isset($a['channels'])?$a['channels']:3;
        if($channels==4&&isset($a['mime'])&&strpos($a['mime'],'jpeg')!==false)$cs='DeviceCMYK';
        elseif($channels==3)$cs='DeviceRGB';
        else $cs='DeviceGray';
        $bpc=isset($a['bits'])?$a['bits']:8;
        $data=file_get_contents($file);
        return ['w'=>$a[0],'h'=>$a[1],'cs'=>$cs,'bpc'=>$bpc,'f'=>'DCTDecode','data'=>$data];
    }

    protected function _parsepng($file){
        $f=fopen($file,'rb');
        if(!$f)$this->Error('Can\'t open image file: '.$file);
        $info=$this->_parsepngstream($f,$file);
        fclose($f);
        return $info;
    }

    protected function _parsepngstream($f,$file){
        if($this->_readstream($f,8)!=chr(137).chr(80).chr(78).chr(71).chr(13).chr(10).chr(26).chr(10))$this->Error('Not a PNG file: '.$file);
        $this->_readstream($f,4);
        if($this->_readstream($f,4)!='IHDR')$this->Error('Incorrect PNG file: '.$file);
        $w=$this->_readint($f);$h=$this->_readint($f);$bpc=ord($this->_readstream($f,1));
        if($bpc>8)$this->Error('16-bit depth not supported: '.$file);
        $ct=ord($this->_readstream($f,1));
        if($ct==0||$ct==4)$cs='DeviceGray';elseif($ct==2||$ct==6)$cs='DeviceRGB';elseif($ct==3)$cs='Indexed';
        else $this->Error('Unknown color type: '.$file);
        if(ord($this->_readstream($f,1))!=0)$this->Error('Unknown compression method: '.$file);
        if(ord($this->_readstream($f,1))!=0)$this->Error('Unknown filter method: '.$file);
        if(ord($this->_readstream($f,1))!=0)$this->Error('Interlacing not supported: '.$file);
        $this->_readstream($f,4);$parms='/DecodeParms <</Predictor 15 /Colors '.($cs=='DeviceRGB'?3:1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
        $pal='';$trns='';$data='';
        do{
            $n=$this->_readint($f);$type=$this->_readstream($f,4);
            if($type=='PLTE'){$pal=$this->_readstream($f,$n);$this->_readstream($f,4);}
            elseif($type=='tRNS'){$t=$this->_readstream($f,$n);if($ct==0)$trns=[ord(substr($t,1,1))];elseif($ct==2)$trns=[ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1))];else{$pos=strpos($t,chr(0));if($pos!==false)$trns=[$pos];}$this->_readstream($f,4);}
            elseif($type=='IDAT'){$data.=$this->_readstream($f,$n);$this->_readstream($f,4);}
            elseif($type=='IEND')break;
            else $this->_readstream($f,$n+4);
        }while($n);
        if($cs=='Indexed'&&empty($pal))$this->Error('Missing palette in '.$file);
        $info=['w'=>$w,'h'=>$h,'cs'=>$cs,'bpc'=>$bpc,'f'=>'FlateDecode','parms'=>$parms,'pal'=>$pal,'trns'=>$trns];
        if($ct>=4){$data=gzuncompress($data);$data2='';$color='';$alpha='';if($ct==4){$len=2*$w;for($i=0;$i<$h;$i++){$pos=($i*$len)+$i;$color.=$data[$pos];$alpha.=substr($data,$pos+1,1);for($pixel=1;$pixel<$w;$pixel++){$color.=substr($data,$pos+1+$pixel*2,1);$alpha.=substr($data,$pos+1+$pixel*2+1,1);}}}else{$len=4*$w;for($i=0;$i<$h;$i++){$pos=($i*$len)+$i;$color.=$data[$pos];for($pixel=1;$pixel<$w;$pixel++){$color.=substr($data,$pos+$pixel*4,3);$alpha.=substr($data,$pos+$pixel*4+3,1);}}}unset($data);$data=gzcompress($color);$info['smask']=gzcompress($alpha);$this->WithAlpha=true;if($this->PDFVersion<'1.4')$this->PDFVersion='1.4';}
        $info['data']=$data;
        return $info;
    }

    protected function _readstream($f,$n){$res='';while($n>0&&!feof($f)){$s=fread($f,$n);if($s===false)$this->Error('Error while reading stream');$n-=strlen($s);$res.=$s;}if($n>0)$this->Error('Unexpected end of stream');return $res;}
    protected function _readint($f){$a=unpack('Ni',$this->_readstream($f,4));return $a['i'];}

    function Output($dest='',$name='',$isUTF8=false){
        $this->Close();
        if($dest=='')$dest='I';
        if($dest=='I'||$dest=='D'){
            if(headers_sent($file,$line))$this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
            header('Content-Type: application/pdf');
            header('Content-Disposition: '.($dest=='D'?'attachment':'inline').'; filename="'.$name.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
        } elseif($dest=='F'){
            $f=fopen($name,'wb');if(!$f)$this->Error('Unable to create output file: '.$name);fwrite($f,$this->buffer);fclose($f);
        } elseif($dest=='S'){
            return $this->buffer;
        } else $this->Error('Incorrect output destination: '.$dest);
        return '';
    }

    protected function _beginpage($orientation,$size,$rotation){
        $this->page++;$this->pages[$this->page]='';$this->PageInfo[$this->page]=[];$this->state=2;$this->x=$this->lMargin;$this->y=$this->tMargin;$this->FontFamily='';
        if(!$orientation)$orientation=$this->DefOrientation;else $orientation=strtoupper($orientation[0]);
        if(!$size)$size=$this->DefPageSize;else $size=$this->_getpagesize($size);
        if($orientation!=$this->CurOrientation||$size[0]!=$this->CurPageSize[0]||$size[1]!=$this->CurPageSize[1]){
            if($orientation=='P'){$this->w=$size[0]/$this->k;$this->h=$size[1]/$this->k;}
            else{$this->w=$size[1]/$this->k;$this->h=$size[0]/$this->k;}
            $this->wPt=$this->w*$this->k;$this->hPt=$this->h*$this->k;$this->PageBreakTrigger=$this->h-$this->bMargin;$this->CurOrientation=$orientation;$this->CurPageSize=$size;}
        if($orientation!=$this->DefOrientation||$size[0]!=$this->DefPageSize[0]||$size[1]!=$this->DefPageSize[1])$this->PageInfo[$this->page]['size']=[$this->wPt,$this->hPt];
        if($rotation!=0){if($rotation%90!=0)$this->Error('Invalid rotation value: '.$rotation);$this->PageInfo[$this->page]['rotation']=$rotation;$this->CurRotation=$rotation;}else $this->CurRotation=0;
    }
    protected function _getpagesize($size){if(is_string($size)){$size=strtolower($size);if(!isset($this->StdPageSizes[$size]))$this->Error('Unknown page size: '.$size);$a=$this->StdPageSizes[$size];return [$a[0]/$this->k,$a[1]/$this->k];}else return [$size[0]/$this->k,$size[1]/$this->k];}
    protected function _endpage(){$this->state=1;}
    protected function _newobj($n=null){if($n===null)$n=++$this->n;$this->offsets[$n]=strlen($this->buffer);$this->_out($n.' 0 obj');}
    protected function _putstream($data){$this->_out('stream');$this->_out($data);$this->_out('endstream');}
    protected function _out($s){if($this->state==2)$this->pages[$this->page].=$s."\n";else $this->buffer.=$s."\n";}

    protected function _enddoc(){
        if($this->AliasNbPages!='')foreach($this->pages as &$p)$p=str_replace($this->AliasNbPages,$this->page,$p);
        $this->metadata['CreationDate']=@date('YmdHis');
        $this->_putpages();$this->_putresources();
        $this->_newobj();$this->_out('<<');$this->_putinfo();$this->_out('>>');$this->_out('endobj');
        $this->_newobj();$this->_out('<<');$this->_putcatalog();$this->_out('>>');$this->_out('endobj');
        $o=strlen($this->buffer);$this->_out('xref');$this->_out('0 '.($this->n+1));$this->_out('0000000000 65535 f ');
        for($i=1;$i<=$this->n;$i++)$this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
        $this->_out('trailer');$this->_out('<<');$this->_puttailer();$this->_out('>>');$this->_out('startxref');$this->_out($o);$this->_out('%%EOF');$this->state=3;
    }

    protected function _putpages(){
        $nb=$this->page;
        for($n=1;$n<=$nb;$n++){
            $this->PageInfo[$n]['n']=$this->n+1+2*($n-1);
        }
        for($n=1;$n<=$nb;$n++){
            $this->_putpage($n);
        }
        $this->_newobj();$kids='';
        for($n=1;$n<=$nb;$n++)$kids.=$this->PageInfo[$n]['n'].' 0 R ';
        $this->_out('<<');$this->_out('/Type /Pages');$this->_out('/Kids ['.$kids.']');$this->_out('/Count '.$nb);$this->_out('>>');$this->_out('endobj');
    }

    protected function _putpage($n){
        $this->_newobj();$this->_out('<<');$this->_out('/Type /Page');$this->_out('/Parent 1 0 R');
        if(isset($this->PageInfo[$n]['size']))$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageInfo[$n]['size'][0],$this->PageInfo[$n]['size'][1]));
        if(isset($this->PageInfo[$n]['rotation']))$this->_out('/Rotate '.$this->PageInfo[$n]['rotation']);
        $this->_out('/Resources 2 0 R');
        $this->_out('/Contents '.($this->n+1).' 0 R>>');$this->_out('endobj');
        $p=$this->compress?gzcompress($this->pages[$n]):$this->pages[$n];
        $this->_newobj();$this->_out('<<');
        if($this->compress)$this->_out('/Filter /FlateDecode');
        $this->_out('/Length '.strlen($p));$this->_out('>>');$this->_putstream($p);$this->_out('endobj');
    }

    protected function _putresources(){
        $this->_putfonts();$this->_putimages();
        $this->_newobj(2);$this->_out('<<');$this->_putresourcedict();$this->_out('>>');$this->_out('endobj');
    }

    protected function _putresourcedict(){
        $this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach($this->fonts as $font)$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
        $this->_out('>>');
        $this->_out('/XObject <<');
        foreach($this->images as $image)$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
        $this->_out('>>');
    }

    protected function _putfonts(){
        foreach($this->fonts as $k=>&$font){
            $this->_newobj();$font['n']=$this->n;
            $this->_out('<<');$this->_out('/Type /Font');$this->_out('/Subtype /Type1');$this->_out('/BaseFont /'.$font['name']);
            if($font['name']!='Symbol'&&$font['name']!='ZapfDingbats')$this->_out('/Encoding /WinAnsiEncoding');
            $this->_out('>>');$this->_out('endobj');
        }
    }

    protected function _putimages(){
        foreach($this->images as $file=>&$info){
            $this->_putimage($info);unset($info['data']);if(isset($info['smask']))unset($info['smask']);
        }
    }

    protected function _putimage(&$info){
        if(isset($info['smask'])){$smask=$info;$smask['w']=$info['w'];$smask['h']=$info['h'];$smask['cs']='DeviceGray';$smask['bpc']=8;$smask['f']='FlateDecode';$smask['data']=$info['smask'];$info['smask']=$this->n+1;$this->_putimage($smask);}
        $this->_newobj();$info['n']=$this->n;$this->_out('<<');$this->_out('/Type /XObject');$this->_out('/Subtype /Image');
        $this->_out('/Width '.$info['w']);$this->_out('/Height '.$info['h']);
        if($info['cs']=='Indexed')$this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
        else{$this->_out('/ColorSpace /'.$info['cs']);if($info['cs']=='DeviceCMYK')$this->_out('/Decode [1 0 1 0 1 0 1 0]');}
        $this->_out('/BitsPerComponent '.$info['bpc']);
        if(isset($info['f']))$this->_out('/Filter /'.$info['f']);
        if(isset($info['parms']))$this->_out($info['parms']);
        if(isset($info['trns'])&&is_array($info['trns'])){$trns='';foreach($info['trns'] as $t)$trns.=$t.' '.$t.' ';$this->_out('/Mask ['.$trns.']');}
        if(isset($info['smask']))$this->_out('/SMask '.($info['smask']).' 0 R');
        $this->_out('/Length '.strlen($info['data'])).'>>'; $this->_putstream($info['data']);$this->_out('endobj');
        if($info['cs']=='Indexed'){$this->_newobj();$pal=($this->compress?gzcompress($info['pal']):$info['pal']);$this->_out('<<'.($this->compress?'/Filter /FlateDecode':'').'/Length '.strlen($pal).'>>');$this->_putstream($pal);$this->_out('endobj');}
    }

    protected function _putinfo(){ foreach($this->metadata as $key=>$val)$this->_out('/'.$key.' '.$this->_textstring($val)); }
    protected function _putcatalog(){$n=$this->PageInfo[1]['n'];$this->_out('/Type /Catalog');$this->_out('/Pages 1 0 R');}
    protected function _puttailer(){$this->_out('/Size '.($this->n+1));$this->_out('/Root '.$this->n.' 0 R');$this->_out('/Info '.($this->n-1).' 0 R');}
    protected function _textstring($s){return '('.$this->_escape($s).')';}
    protected function _escape($s){return str_replace(['\\','(',')',"\r"],['\\\\','\\(','\\)','\\r'],$s);}
    protected function _dounderline($x,$y,$txt){$up=$this->CurrentFont['up']??-100;$ut=$this->CurrentFont['ut']??50;$w=$this->GetStringWidth($txt)+$this->ws*substr_count($txt,' ');return sprintf('%.2F %.2F %.2F %.2F re f',$x*$this->k,($this->h-($y-$up/1000*$this->FontSize))*$this->k,$w*$this->k,-$ut/1000*$this->FontSizePt);}
}
