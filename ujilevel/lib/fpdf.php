<?php
/**
 * FPDF - A minimalist implementation for this project
 */

class FPDF {
    public $page;           // current page number
    public $n;              // current object number
    public $offsets;        // array of object offsets
    public $buffer;         // buffer holding in-memory PDF
    public $pages;          // array containing pages
    public $state;          // current document state
    public $compress;       // compression flag
    public $k;              // scale factor (points -> user units)
    public $DefOrientation; // default orientation
    public $CurOrientation; // current orientation
    public $StdPageSizes;   // standard page sizes
    public $DefPageSize;    // default page size
    public $CurPageSize;    // current page size
    public $CurRotation;    // current page rotation
    public $PageInfo;       // page-related data
    public $wPt, $hPt;      // dimensions of current page in points
    public $w, $h;          // dimensions of current page in user units
    public $lMargin;        // left margin
    public $tMargin;        // top margin
    public $rMargin;        // right margin
    public $bMargin;        // page break margin
    public $cMargin;        // cell margin
    public $x, $y;          // current position in user units
    public $lasth;          // height of last printed cell
    public $LineWidth;      // line width in user units
    public $fontpath;       // path containing fonts
    public $CoreFonts;      // array of core font names
    public $fonts;          // array of used fonts
    public $FontFiles;      // array of font files
    public $encodings;      // array of encodings
    public $cmaps;          // array of ToUnicode CMaps
    public $FontFamily;     // current font family
    public $FontStyle;      // current font style
    public $underline;      // underlining flag
    public $CurrentFont;    // current font info
    public $FontSizePt;     // current font size in points
    public $FontSize;       // current font size in user units
    public $DrawColor;      // commands for drawing color
    public $FillColor;      // commands for filling color
    public $TextColor;      // commands for text color
    public $ColorFlag;      // indicates whether fill and text colors are different
    public $WithAlpha;      // indicates whether alpha channel is used
    public $ws;             // word spacing
    public $images;         // array of used images
    public $PageLinks;      // array of links in pages
    public $links;          // array of internal links
    public $AutoPageBreak;  // automatic page breaking
    public $PageBreakTrigger; // threshold used to trigger page breaks
    public $InHeader;       // flag set when processing header
    public $InFooter;       // flag set when processing footer
    public $AliasNbPages;   // alias for total number of pages
    public $ZoomMode;       // zoom display mode
    public $LayoutMode;     // layout display mode
    public $metadata;       // document properties
    public $PDFVersion;     // PDF version number

    function __construct($orientation='P', $unit='mm', $size='A4') {
        // Initialize properties
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = array();
        $this->PageInfo = array();
        $this->state = 0;
        $this->fonts = array();
        $this->FontFiles = array();
        $this->encodings = array();
        $this->cmaps = array();
        $this->images = array();
        $this->links = array();
        $this->InHeader = false;
        $this->InFooter = false;
        $this->lasth = 0;
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->WithAlpha = false;
        $this->ws = 0;
        $this->PDFVersion = '1.7';

        // Standard fonts
        $this->CoreFonts = array(
            'courier'=>'Courier',
            'courierB'=>'Courier-Bold',
            'courierI'=>'Courier-Oblique',
            'courierBI'=>'Courier-BoldOblique',
            'helvetica'=>'Helvetica',
            'helveticaB'=>'Helvetica-Bold',
            'helveticaI'=>'Helvetica-Oblique',
            'helveticaBI'=>'Helvetica-BoldOblique',
            'times'=>'Times-Roman',
            'timesB'=>'Times-Bold',
            'timesI'=>'Times-Italic',
            'timesBI'=>'Times-BoldItalic',
            'symbol'=>'Symbol',
            'zapfdingbats'=>'ZapfDingbats'
        );
        
        // Scale factor
        if($unit == 'pt')
            $this->k = 1;
        elseif($unit == 'mm')
            $this->k = 72/25.4;
        elseif($unit == 'cm')
            $this->k = 72/2.54;
        elseif($unit == 'in')
            $this->k = 72;
        else
            $this->Error('Incorrect unit: '.$unit);
            
        // Page sizes
        $this->StdPageSizes = array(
            'a3' => array(841.89, 1190.55),
            'a4' => array(595.28, 841.89),
            'a5' => array(420.94, 595.28),
            'letter' => array(612, 792),
            'legal' => array(612, 1008)
        );
        
        // Default orientation and size
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        
        // Page orientation
        $orientation = strtolower($orientation);
        if($orientation == 'p' || $orientation == 'portrait') {
            $this->DefOrientation = 'P';
            $this->w = $size[0];
            $this->h = $size[1];
        } elseif($orientation == 'l' || $orientation == 'landscape') {
            $this->DefOrientation = 'L';
            $this->w = $size[1];
            $this->h = $size[0];
        } else {
            $this->Error('Incorrect orientation: '.$orientation);
        }
        
        $this->CurOrientation = $this->DefOrientation;
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;
        
        // Page margins (1 cm)
        $margin = 28.35 / $this->k;
        $this->SetMargins($margin, $margin);
        
        // Interior cell margin (1 mm)
        $this->cMargin = $margin / 10;
        
        // Line width (0.2 mm)
        $this->LineWidth = 0.567 / $this->k;
        
        // Automatic page break
        $this->SetAutoPageBreak(true, 2 * $margin);
        
        // Default display mode
        $this->SetDisplayMode('default');
        
        // Enable compression
        $this->SetCompression(true);
    }

    function SetMargins($left, $top, $right=null) {
        // Set left, top and right margins
        $this->lMargin = $left;
        $this->tMargin = $top;
        if($right === null)
            $right = $left;
        $this->rMargin = $right;
    }

    function SetAutoPageBreak($auto, $margin=0) {
        // Set auto page break mode and triggering margin
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    function SetDisplayMode($zoom, $layout='default') {
        // Set display mode in viewer
        $this->ZoomMode = $zoom;
        $this->LayoutMode = $layout;
    }

    function SetCompression($compress) {
        // Set page compression
        $this->compress = $compress;
    }

    function SetTitle($title, $isUTF8=false) {
        // Title of document
        if(!isset($this->metadata['Title']))
            $this->metadata['Title'] = $title;
    }

    function SetAuthor($author, $isUTF8=false) {
        // Author of document
        if(!isset($this->metadata['Author']))
            $this->metadata['Author'] = $author;
    }

    function AddPage($orientation='', $size='', $rotation=0) {
        // Start a new page
        if($this->state == 0)
            $this->Open();
            
        $orientation = $orientation ? $orientation : $this->DefOrientation;
        $size = $size ? $size : $this->DefPageSize;
        
        // Reset current position
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        
        // Page number
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        // Output a cell
        $k = $this->k;
        
        if($border || $fill || $this->y + $h > $this->PageBreakTrigger) {
            $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', 
                $this->x * $k, ($this->h - $this->y) * $k, 
                $w * $k, -$h * $k, 
                $fill ? 'f' : 'S')
            );
        }
        
        if($txt !== '') {
            $this->_out('BT '.sprintf('%.2F %.2F Td (%s) Tj ET', 
                $this->x * $k, 
                ($this->h - $this->y - $h/2) * $k, 
                $this->_escape($txt))
            );
        }
        
        if($ln > 0) {
            // Go to the next line
            $this->y += $h;
            if($ln == 1)
                $this->x = $this->lMargin;
        } else
            $this->x += $w;
    }

    function Text($x, $y, $txt) {
        // Output a string
        $s = sprintf('BT %.2F %.2F Td (%s) Tj ET', 
            $x * $this->k, 
            ($this->h - $y) * $this->k, 
            $this->_escape($txt));
            
        $this->_out($s);
    }

    function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='') {
        // Simplified image handling for this implementation
        // Just output a placeholder
        $this->Cell($w, $h, '[Image]', 1, 0, 'C');
    }

    function Ln($h=null) {
        // Line feed; default value is the last cell height
        $this->x = $this->lMargin;
        if($h === null)
            $this->y += $this->lasth;
        else
            $this->y += $h;
    }

    function SetFont($family, $style='', $size=0) {
        // Select a font; size given in points
        if($size == 0)
            $size = $this->FontSizePt;
            
        // Set the font
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
    }

    function Output($dest='', $name='', $isUTF8=false) {
        // Simplified output function
        if($dest == 'S')
            return $this->buffer;
            
        // Send to browser
        if(php_sapi_name() != 'cli') {
            // We send to a browser
            header('Content-Type: application/pdf');
            if(headers_sent())
                $this->Error('Headers already sent');
                
            header('Content-Length: '.strlen($this->buffer));
            header('Content-Disposition: inline; filename="'.$name.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
        } else
            return $this->buffer;
    }

    function _getpagesize($size) {
        if(is_string($size)) {
            $size = strtolower($size);
            if(!isset($this->StdPageSizes[$size]))
                $this->Error('Unknown page size: '.$size);
            return $this->StdPageSizes[$size];
        } else {
            if($size[0] > $size[1])
                return array($size[1], $size[0]);
            else
                return $size;
        }
    }

    function _escape($s) {
        // Escape special characters in strings
        return str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $s)));
    }

    function _out($s) {
        // Add a line to the document
        if($this->state == 2)
            $this->pages[$this->page] .= $s."\n";
        else
            $this->buffer .= $s."\n";
    }

    function SetFillColor($r, $g=null, $b=null) {
        // Set fill color
        if(($r==0 && $g==0 && $b==0) || $g===null)
            $this->FillColor = sprintf('%.3F g', $r/255);
        else
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
    }

    function Error($msg) {
        // Fatal error
        throw new Exception('FPDF error: '.$msg);
    }
    
    function Open() {
        // Begin document
        $this->state = 1;
    }

    function SetY($y, $resetX=true) {
        // Set the y position and optionally reset the x position
        $this->y = $y;
        if($resetX)
            $this->x = $this->lMargin;
    }
    
    function SetX($x) {
        // Set the x position
        $this->x = $x;
    }
    
    function SetXY($x, $y) {
        // Set x and y positions
        $this->SetY($y, false);
        $this->SetX($x);
    }
    
    function Rect($x, $y, $w, $h, $style='') {
        // Draw a rectangle
        if($style=='F')
            $op = 'f'; // Fill
        elseif($style=='FD' || $style=='DF')
            $op = 'B'; // Fill and stroke
        else
            $op = 'S'; // Stroke
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x*$this->k, ($this->h-$y)*$this->k, $w*$this->k, -$h*$this->k, $op));
    }
    
    function PageNo() {
        // Get current page number
        return $this->page;
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        // Output text with automatic or explicit line breaks
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w - $this->rMargin - $this->x;
        
        $wmax = ($w - 2 * $this->cMargin);
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        
        $b = 0;
        if($border) {
            if($border==1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if(strpos($border, 'L')!==false)
                    $b2 .= 'L';
                if(strpos($border, 'R')!==false)
                    $b2 .= 'R';
                $b = $b2 . (strpos($border, 'T')!==false ? 'T' : '');
            }
        }
        
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        
        while($i < $nb) {
            // Get next character
            $c = $s[$i];
            
            // Check for line break or paragraph
            if($c == "\n") {
                // Explicit line break
                if($border && $nl==1) {
                    $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                } else {
                    $this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, $align, $fill);
                }
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                
                if($border && $nl==2)
                    $b = $b2;
                continue;
            }
            
            if($c == ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            
            $l += 1; // Simplified character width handling
            
            // Automatic line break
            if($l > $wmax) {
                if($sep == -1) {
                    // Very long word - force break
                    if($i == $j)
                        $i++;
                    
                    if($border && $nl==1) {
                        $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                    } else {
                        $this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, $align, $fill);
                    }
                } else {
                    if($align=='J') {
                        $this->Cell($w, $h, substr($s, $j, $sep-$j), 0, 2, $align, $fill);
                    } else {
                        $this->Cell($w, $h, substr($s, $j, $sep-$j), 0, 2, $align, $fill);
                    }
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                
                if($border && $nl==2)
                    $b = $b2;
            } else {
                $i++;
            }
        }
        
        // Last line
        if($border && strpos($border, 'B')!==false) {
            $b .= 'B';
        }
        $this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
        $this->x = $this->lMargin;
    }
}

class PDF extends FPDF {
    // Page header
    function Header() {
        // Logo
        // $this->Image('logo.png', 10, 10, 30);
        
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        
        // Move to the right
        $this->Cell(80);
        
        // Title
        $this->Cell(30, 10, 'KARTU PELAJAR', 0, 0, 'C');
        
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        
        // Page number
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
    
    // Override Image method to actually display images
    function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='') {
        // Put an image on the page
        if($x===null)
            $x = $this->x;
        if($y===null)
            $y = $this->y;
            
        // Check if image exists
        if(!file_exists($file)) {
            $this->Rect($x, $y, $w, $h);
            $this->SetXY($x, $y + $h/2 - 2);
            $this->Cell($w, 4, '[FOTO TIDAK DITEMUKAN]', 0, 0, 'C');
            return;
        }
            
        // Get image dimensions
        list($width, $height) = getimagesize($file);
        
        // Determine image type
        if(empty($type)) {
            $pos = strrpos($file, '.');
            if($pos === false)
                $this->Error('Image file has no extension and no type was specified');
            $type = substr($file, $pos+1);
        }
        $type = strtolower($type);
        
        // In a full FPDF implementation, this would display the actual image
        // For our simplified version, we'll just create a placeholder with the name
        $short_filename = basename($file);
        $this->Rect($x, $y, $w, $h);
        
        // If we can access the real file, display a better placeholder
        $image_info = @getimagesize($file);
        if($image_info !== false) {
            // Create a placeholder with file info
            $this->SetFillColor(240, 240, 240);
            $this->Rect($x, $y, $w, $h, 'F');
            $this->SetXY($x, $y + $h/2 - 4);
            $this->SetFont('Arial', '', 6);
            $this->Cell($w, 4, '[FOTO SISWA]', 0, 1, 'C');
            $this->SetXY($x, $y + $h/2);
            $this->Cell($w, 4, basename($file), 0, 0, 'C');
        } else {
            $this->SetXY($x, $y + $h/2 - 2);
            $this->Cell($w, 4, '[FOTO]', 0, 0, 'C');
        }
    }
} 