<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Report;

use Fisharebest\Webtrees\Functions\FunctionsRtl;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\MediaFile;
use Fisharebest\Webtrees\Webtrees;

/**
 * Class ReportHtml
 */
class ReportHtml extends AbstractReport
{
    /**
     * Cell padding
     *
     * @var float
     */
    public $cPadding = 2;

    /**
     * Cell height ratio
     *
     * @var float
     */
    public $cellHeightRatio = 1.8;

    /**
     * Current horizontal position
     *
     * @var float
     */
    public $X = 0.0;

    /**
     * Current vertical position
     *
     * @var float
     */
    public $Y = 0.0;

    /**
     * Currently used style name
     *
     * @var string
     */
    public $currentStyle = '';

    /**
     * Page number counter
     *
     * @var int
     */
    public $pageN = 1;

    /**
     * Store the page width without left and right margins
     *
     * In HTML, we don't need this
     *
     * @var float
     */
    public $noMarginWidth = 0.0;

    /**
     * Last cell height
     *
     * @var float
     */
    public $lastCellHeight = 0.0;

    /**
     * LTR or RTL alignement; "left" on LTR, "right" on RTL
     * Used in <div>
     *
     * @var string
     */
    public $alignRTL = 'left';

    /**
     * LTR or RTL entity
     *
     * @var string
     */
    public $entityRTL = '&lrm;';

    /**
     * Largest Font Height is used by TextBox etc.
     *
     * Use this to calculate a the text height.
     * This makes sure that the text fits into the cell/box when different font sizes are used
     *
     * @var float
     */
    public $largestFontHeight = 0;

    /**
     * Keep track of the highest Y position
     *
     * Used with Header div / Body div / Footer div / "addpage" / The bottom of the last image etc.
     *
     * @var float
     */
    public $maxY = 0;

    /** @var ReportBaseElement[] Array of elements in the header */
    public $headerElements = [];

    /** @var ReportBaseElement[] Array of elements in the page header */
    public $pageHeaderElements = [];

    /** @var ReportBaseElement[] Array of elements in the footer */
    public $footerElements = [];

    /** @var ReportBaseElement[] Array of elements in the body */
    public $bodyElements = [];

    /** @var ReportHtmlFootnote[] Array of elements in the footer notes */
    public $printedfootnotes = [];

    /**
     * HTML Setup - ReportHtml
     *
     * @return void
     */
    public function setup(): void
    {
        parent::setup();

        // Setting up the correct dimensions if Portrait (default) or Landscape
        if ($this->orientation === 'landscape') {
            $tmpw              = $this->page_width;
            $this->page_width  = $this->page_height;
            $this->page_height = $tmpw;
        }
        // Store the pagewidth without margins
        $this->noMarginWidth = $this->page_width - $this->left_margin - $this->right_margin;
        // If RTL
        if ($this->rtl) {
            $this->alignRTL  = 'right';
            $this->entityRTL = '&rlm;';
        }
        // Change the default HTML font name
        $this->default_font = 'Arial';

        if ($this->show_generated_by) {
            // The default style name for Generated by.... is 'genby'
            $element = new ReportHtmlCell(0, 10, 0, 'C', '', 'genby', 1, ReportBaseElement::CURRENT_POSITION, ReportBaseElement::CURRENT_POSITION, 0, 0, '', '', true);
            $element->addText($this->generated_by);
            $element->setUrl(Webtrees::VERSION);
            $this->footerElements[] = $element;
        }
    }

    /**
     * Add an element.
     *
     * @param ReportBaseElement|string $element
     *
     * @return void
     */
    public function addElement($element)
    {
        if ($this->processing === 'B') {
            $this->bodyElements[] = $element;
        } elseif ($this->processing === 'H') {
            $this->headerElements[] = $element;
        } elseif ($this->processing === 'F') {
            $this->footerElements[] = $element;
        }
    }

    /**
     * Generate the page header
     *
     * @return void
     */
    private function runPageHeader()
    {
        foreach ($this->pageHeaderElements as $element) {
            if ($element instanceof ReportBaseElement) {
                $element->render($this);
            } elseif ($element === 'footnotetexts') {
                $this->footnotes();
            } elseif ($element === 'addpage') {
                $this->addPage();
            }
        }
    }

    /**
     * Generate footnotes
     *
     * @return void
     */
    public function footnotes()
    {
        $this->currentStyle = '';
        if (!empty($this->printedfootnotes)) {
            foreach ($this->printedfootnotes as $element) {
                $element->renderFootnote($this);
            }
        }
    }

    /**
     * Run the report.
     *
     * @return void
     */
    public function run()
    {
        // Setting up the styles
        echo '<style type="text/css">';
        echo '#bodydiv { font: 10px sans-serif;}';
        foreach ($this->styles as $class => $style) {
            echo '.', $class, ' { ';
            if ($style['font'] === 'dejavusans') {
                $style['font'] = $this->default_font;
            }
            echo 'font-family: ', $style['font'], '; ';
            echo 'font-size: ', $style['size'], 'pt; ';
            // Case-insensitive
            if (stripos($style['style'], 'B') !== false) {
                echo 'font-weight: bold; ';
            }
            if (stripos($style['style'], 'I') !== false) {
                echo 'font-style: italic; ';
            }
            if (stripos($style['style'], 'U') !== false) {
                echo 'text-decoration: underline; ';
            }
            if (stripos($style['style'], 'D') !== false) {
                echo 'text-decoration: line-through; ';
            }
            echo '}', PHP_EOL;
        }
        unset($class, $style);
        //-- header divider
        echo '</style>', PHP_EOL;
        echo '<div id="headermargin" style="position: relative; top: auto; height: ', $this->header_margin, 'pt; width: ', $this->noMarginWidth, 'pt;"></div>';
        echo '<div id="headerdiv" style="position: relative; top: auto; width: ', $this->noMarginWidth, 'pt;">';
        foreach ($this->headerElements as $element) {
            if ($element instanceof ReportBaseElement) {
                $element->render($this);
            } elseif ($element === 'footnotetexts') {
                $this->footnotes();
            } elseif ($element === 'addpage') {
                $this->addPage();
            }
        }
        //-- body
        echo '</div>';
        echo '<script>document.getElementById("headerdiv").style.height="', $this->top_margin - $this->header_margin - 6, 'pt";</script>';
        echo '<div id="bodydiv" style="position: relative; top: auto; width: ', $this->noMarginWidth, 'pt; height: 100%;">';
        $this->Y    = 0;
        $this->maxY = 0;
        $this->runPageHeader();
        foreach ($this->bodyElements as $element) {
            if ($element instanceof ReportBaseElement) {
                $element->render($this);
            } elseif ($element === 'footnotetexts') {
                $this->footnotes();
            } elseif ($element === 'addpage') {
                $this->addPage();
            }
        }
        //-- footer
        echo '</div>';
        echo '<script>document.getElementById("bodydiv").style.height="', $this->maxY, 'pt";</script>';
        echo '<div id="bottommargin" style="position: relative; top: auto; height: ', $this->bottom_margin - $this->footer_margin, 'pt;width:', $this->noMarginWidth, 'pt;"></div>';
        echo '<div id="footerdiv" style="position: relative; top: auto; width: ', $this->noMarginWidth, 'pt;height:auto;">';
        $this->Y    = 0;
        $this->X    = 0;
        $this->maxY = 0;
        foreach ($this->footerElements as $element) {
            if ($element instanceof ReportBaseElement) {
                $element->render($this);
            } elseif ($element === 'footnotetexts') {
                $this->footnotes();
            } elseif ($element === 'addpage') {
                $this->addPage();
            }
        }
        echo '</div>';
        echo '<script>document.getElementById("footerdiv").style.height="', $this->maxY, 'pt";</script>';
        echo '<div id="footermargin" style="position: relative; top: auto; height: ', $this->footer_margin, 'pt;width:', $this->noMarginWidth, 'pt;"></div>';
    }

    /**
     * Create a new Cell object.
     *
     * @param int    $width   cell width (expressed in points)
     * @param int    $height  cell height (expressed in points)
     * @param mixed  $border  Border style
     * @param string $align   Text alignement
     * @param string $bgcolor Background color code
     * @param string $style   The name of the text style
     * @param int    $ln      Indicates where the current position should go after the call
     * @param mixed  $top     Y-position
     * @param mixed  $left    X-position
     * @param int    $fill    Indicates if the cell background must be painted (1) or transparent (0). Default value: 1
     * @param int    $stretch Stretch carachter mode
     * @param string $bocolor Border color
     * @param string $tcolor  Text color
     * @param bool   $reseth
     *
     * @return ReportBaseCell
     */
    public function createCell($width, $height, $border, $align, $bgcolor, $style, $ln, $top, $left, $fill, $stretch, $bocolor, $tcolor, $reseth): ReportBaseCell
    {
        return new ReportHtmlCell($width, $height, $border, $align, $bgcolor, $style, $ln, $top, $left, $fill, $stretch, $bocolor, $tcolor, $reseth);
    }

    /**
     * Create a new TextBox object.
     *
     * @param float  $width   Text box width
     * @param float  $height  Text box height
     * @param bool   $border
     * @param string $bgcolor Background color code in HTML
     * @param bool   $newline
     * @param float  $left
     * @param float  $top
     * @param bool   $pagecheck
     * @param string $style
     * @param bool   $fill
     * @param bool   $padding
     * @param bool   $reseth
     *
     * @return ReportBaseTextbox
     */
    public function createTextBox(
        float $width,
        float $height,
        bool $border,
        string $bgcolor,
        bool $newline,
        float $left,
        float $top,
        bool $pagecheck,
        string $style,
        bool $fill,
        bool $padding,
        bool $reseth
    ): ReportBaseTextbox {
        return new ReportHtmlTextbox($width, $height, $border, $bgcolor, $newline, $left, $top, $pagecheck, $style, $fill, $padding, $reseth);
    }

    /**
     * Create a text element.
     *
     * @param string $style
     * @param string $color
     *
     * @return ReportBaseText
     */
    public function createText(string $style, string $color): ReportBaseText
    {
        return new ReportHtmlText($style, $color);
    }

    /**
     * Create a new Footnote object.
     *
     * @param string $style Style name
     *
     * @return ReportBaseFootnote
     */
    public function createFootnote($style): ReportBaseFootnote
    {
        return new ReportHtmlFootnote($style);
    }

    /**
     * Create a new Page Header object
     *
     * @return ReportBasePageheader
     */
    public function createPageHeader(): ReportBasePageheader
    {
        return new ReportHtmlPageheader();
    }

    /**
     * Create a new image object.
     *
     * @param string $file  Filename
     * @param float  $x
     * @param float  $y
     * @param float  $w     Image width
     * @param float  $h     Image height
     * @param string $align L:left, C:center, R:right or empty to use x/y
     * @param string $ln    T:same line, N:next line
     *
     * @return ReportBaseImage
     */
    public function createImage(string $file, float $x, float $y, float $w, float $h, string $align, string $ln): ReportBaseImage
    {
        return new ReportHtmlImage($file, $x, $y, $w, $h, $align, $ln);
    }

    /**
     * Create a new image object from Media Object.
     *
     * @param MediaFile $media_file
     * @param float     $x
     * @param float     $y
     * @param float     $w     Image width
     * @param float     $h     Image height
     * @param string    $align L:left, C:center, R:right or empty to use x/y
     * @param string    $ln    T:same line, N:next line
     *
     * @return ReportBaseImage
     */
    public function createImageFromObject(MediaFile $media_file, float $x, float $y, float $w, float $h, string $align, string $ln): ReportBaseImage
    {
        return new ReportHtmlImage($media_file->imageUrl((int) $w, (int) $h, ''), $x, $y, $w, $h, $align, $ln);
    }

    /**
     * Create a line.
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     *
     * @return ReportBaseLine
     */
    public function createLine(float $x1, float $y1, float $x2, float $y2): ReportBaseLine
    {
        return new ReportHtmlLine($x1, $y1, $x2, $y2);
    }

    /**
     * Create an HTML element.
     *
     * @param string   $tag
     * @param string[] $attrs
     *
     * @return ReportBaseHtml
     */
    public function createHTML(string $tag, array $attrs): ReportBaseHtml
    {
        return new ReportHtmlHtml($tag, $attrs);
    }

    /**
     * Clear the Header
     *
     * @return void
     */
    public function clearHeader()
    {
        $this->headerElements = [];
    }

    /**
     * Update the Page Number and set a new Y if max Y is larger - ReportHtml
     *
     * @return void
     */
    public function addPage()
    {
        $this->pageN++;

        // Add a little margin to max Y "between pages"
        $this->maxY += 10;

        // If Y is still heigher by any reason...
        if ($this->maxY < $this->Y) {
            // ... update max Y
            $this->maxY = $this->Y;
        } else {
            // else update Y so that nothing will be overwritten, like images or cells...
            $this->Y = $this->maxY;
        }
    }

    /**
     * Uppdate max Y to keep track it incase of a pagebreak - ReportHtml
     *
     * @param float $y
     *
     * @return void
     */
    public function addMaxY($y)
    {
        if ($this->maxY < $y) {
            $this->maxY = $y;
        }
    }

    /**
     * Add a page header.
     *
     * @param $element
     *
     * @return void
     */
    public function addPageHeader($element)
    {
        $this->pageHeaderElements[] = $element;
    }

    /**
     * Checks the Footnote and numbers them - ReportHtml
     *
     * @param ReportHtmlFootnote $footnote
     *
     * @return ReportHtmlFootnote|bool object if already numbered, false otherwise
     */
    public function checkFootnote(ReportHtmlFootnote $footnote)
    {
        $ct  = count($this->printedfootnotes);
        $i   = 0;
        $val = $footnote->getValue();
        while ($i < $ct) {
            if ($this->printedfootnotes[$i]->getValue() == $val) {
                // If this footnote already exist then set up the numbers for this object
                $footnote->setNum($i + 1);
                $footnote->setAddlink((string) ($i + 1));

                return $this->printedfootnotes[$i];
            }
            $i++;
        }
        // If this Footnote has not been set up yet
        $footnote->setNum($ct + 1);
        $footnote->setAddlink((string) ($ct + 1));
        $this->printedfootnotes[] = $footnote;

        return false;
    }

    /**
     * Clear the Page Header - ReportHtml
     *
     * @return void
     */
    public function clearPageHeader()
    {
        $this->pageHeaderElements = [];
    }

    /**
     * Count the number of lines - ReportHtml
     *
     * @param string $str
     *
     * @return int Number of lines. 0 if empty line
     */
    public function countLines($str): int
    {
        if ($str === '') {
            return 0;
        }

        return substr_count($str, "\n") + 1;
    }

    /**
     * Get the current style.
     *
     * @return string
     */
    public function getCurrentStyle(): string
    {
        return $this->currentStyle;
    }

    /**
     * Get the current style height.
     *
     * @return float
     */
    public function getCurrentStyleHeight(): float
    {
        if (empty($this->currentStyle)) {
            return $this->default_font_size;
        }
        $style = $this->getStyle($this->currentStyle);

        return $style['size'];
    }

    /**
     * Get the current footnotes height.
     *
     * @param float $cellWidth
     *
     * @return float
     */
    public function getFootnotesHeight(float $cellWidth): float
    {
        $h = 0;
        foreach ($this->printedfootnotes as $element) {
            $h += $element->getFootnoteHeight($this, $cellWidth);
        }

        return $h;
    }

    /**
     * Get the maximum width from current position to the margin - ReportHtml
     *
     * @return float
     */
    public function getRemainingWidth(): float
    {
        return $this->noMarginWidth - $this->X;
    }

    /**
     * Get the page height.
     *
     * @return float
     */
    public function getPageHeight(): float
    {
        return $this->page_height - $this->top_margin;
    }

    /**
     * Get the width of a string.
     *
     * @param string $text
     *
     * @return float
     */
    public function getStringWidth(string $text): float
    {
        $style = $this->getStyle($this->currentStyle);

        return mb_strlen($text) * ($style['size'] / 2);
    }

    /**
     * Get a text height in points - ReportHtml
     *
     * @param string $str
     *
     * @return float
     */
    public function getTextCellHeight(string $str): float
    {
        // Count the number of lines to calculate the height
        $nl = $this->countLines($str);

        // Calculate the cell height
        return ceil(($this->getCurrentStyleHeight() * $this->cellHeightRatio) * $nl);
    }

    /**
     * Get the current X position - ReportHtml
     *
     * @return float
     */
    public function getX(): float
    {
        return $this->X;
    }

    /**
     * Get the current Y position - ReportHtml
     *
     * @return float
     */
    public function getY(): float
    {
        return $this->Y;
    }

    /**
     * Get the current page number - ReportHtml
     *
     * @return int
     */
    public function pageNo(): int
    {
        return $this->pageN;
    }

    /**
     * Set the current style.
     *
     * @param string $s
     *
     * @void
     */
    public function setCurrentStyle(string $s)
    {
        $this->currentStyle = $s;
    }

    /**
     * Set the X position - ReportHtml
     *
     * @param float $x
     *
     * @return void
     */
    public function setX($x)
    {
        $this->X = $x;
    }

    /**
     * Set the Y position - ReportHtml
     *
     * Also updates Max Y position
     *
     * @param float $y
     *
     * @return void
     */
    public function setY($y)
    {
        $this->Y = $y;
        if ($this->maxY < $y) {
            $this->maxY = $y;
        }
    }

    /**
     * Set the X and Y position - ReportHtml
     *
     * Also updates Max Y position
     *
     * @param float $x
     * @param float $y
     *
     * @return void
     */
    public function setXy($x, $y)
    {
        $this->setX($x);
        $this->setY($y);
    }

    /**
     * Wrap text - ReportHtml
     *
     * @param string $str   Text to wrap
     * @param float  $width Width in points the text has to fit into
     *
     * @return string
     */
    public function textWrap(string $str, float $width): string
    {
        // Calculate the line width
        $lw = (int) ($width / ($this->getCurrentStyleHeight() / 2));
        // Wordwrap each line
        $lines = explode("\n", $str);
        // Line Feed counter
        $lfct     = count($lines);
        $wraptext = '';
        foreach ($lines as $line) {
            $wtext = FunctionsRtl::utf8WordWrap($line, $lw, "\n", true);
            $wraptext .= $wtext;
            // Add a new line as long as it’s not the last line
            if ($lfct > 1) {
                $wraptext .= "\n";
            }
            $lfct--;
        }

        return $wraptext;
    }

    /**
     * Write text - ReportHtml
     *
     * @param string $text  Text to print
     * @param string $color HTML RGB color code (Ex: #001122)
     * @param bool   $useclass
     *
     * @return void
     */
    public function write($text, $color = '', $useclass = true)
    {
        $style    = $this->getStyle($this->getCurrentStyle());
        $htmlcode = '<span dir="' . I18N::direction() . '"';
        if ($useclass) {
            $htmlcode .= ' class="' . $style['name'] . '"';
        }
        if (!empty($color)) {
            // Check if Text Color is set and if it’s valid HTML color
            if (preg_match('/#?(..)(..)(..)/', $color)) {
                $htmlcode .= ' style="color:' . $color . ';"';
            }
        }

        $htmlcode .= '>' . $text . '</span>';
        $htmlcode = str_replace([
            "\n",
            '> ',
            ' <',
        ], [
            '<br>',
            '>&nbsp;',
            '&nbsp;<',
        ], $htmlcode);
        echo $htmlcode;
    }
}
