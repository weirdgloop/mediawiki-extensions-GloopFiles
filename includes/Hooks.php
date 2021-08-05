<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\GloopFiles;

use MediaWiki\MediaWikiServices;
use Language;

class Hooks {

    /**
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/MimeMagicInit
     * @param  MimeAnalyzer $mime
     * @return null
     */
    public static function onMimeMagicInit( $mime ) {
        $mime->addExtraInfo( 'application/vnd.marmoset' );
        $mime->addExtraTypes( 'application/vnd.marmoset [3D]' );
    }

    /**
     * @param ImagePage $imagePage the imagepage that is being rendered
     * @param OutputPage $out the output for this imagepage
     * @return bool
     */
    public static function onImageOpenShowImageInlineBefore( ImagePage $imagePage, OutputPage $out ) {
        $file = $imagePage->getDisplayedFile();
        return self::onImagePageHooks( $file, $out );
    }

    /**
     * @param ImagePage $imagePage that is being rendered
     * @param File $file the (old) file added in this history entry
     * @param string &$line the HTML of the history line
     * @param string &$css the CSS class of the history line
     * @return bool
     */
    public static function onImagePageFileHistoryLine( $imagePage, $file, &$line, &$css ) {
        $out = $imagePage->getContext()->getOutput();
        return self::onImagePageHooks( $file, $out );
    }

    /**
     * @param File $file the file that is being rendered
     * @param OutputPage $out the output to which this file is being rendered
     * @return bool
     */
    private static function onImagePageHooks( $file, $out ) {
        $handler = $file->getHandler();
        if ( $handler !== false && $handler instanceof MarmosetHandler ) {
            //$parserOutput->addModuleStyles( 'ext.gloopfiles.marmoset.styles' );
            $parserOutput->addModules( 'ext.gloopfiles.marmoset' );
        }
        return true;
    }

    /**
     * Add JavaScript and CSS for special pages that may include timed mview
     * files but which will not fire the parser hook.
     *
     * FIXME: There ought to be a better interface for determining whether the
     * page is liable to contain mview files.
     *
     * @param OutputPage $out
     * @param Skin $sk
     * @return bool
     */
    public static function pageOutputHook( OutputPage $out, Skin $sk ) {
        $title = $out->getTitle();
        $namespace = $title->getNamespace();
        $addModules = false;

        if ( $namespace === NS_CATEGORY ) {
            $addModules = true;
        }

        if ( $title->isSpecialPage() ) {
            list( $name, /* subpage */ ) = MediaWikiServices::getInstance()
                ->getSpecialPageFactory()->resolveAlias( $title->getDBkey() );
            if ( stripos( $name, 'file' ) !== false || stripos( $name, 'image' ) !== false
                || $name === 'Search' || $name === 'GlobalUsage' || $name === 'Upload' ) {
                    $addModules = true;
            }
        }

        if ( $addModules ) {
            //$parserOutput->addModuleStyles( 'ext.gloopfiles.marmoset.styles' );
            $parserOutput->addModules( 'ext.gloopfiles.marmoset' );
        }

        return true;
    }

    /**
     * Return false here to evict existing parseroutput cache
     * @param ParserOutput $parserOutput
     * @param WikiPage $wikiPage
     * @param ParserOutput $parserOptions
     * @return bool
     */
    public static function onRejectParserCacheValue( $parserOutput, $wikiPage, $parserOptions ) {
        if ( $parserOutput->getExtensionData( 'mw_ext_GF_hasMarmoset' ) &&
            !in_array( 'ext.gloopfiles.marmoset', $parserOutput->getModules() ) ) {
            return false;
        }
        return true;
    }

    /**
     * Called before producing the HTML created by a wiki image insertion
     * @param  DummyLinker &$dummy
     * @param  Title &$title
     * @param  File|bool &$file
     * @param  array &$frameParams Associative array of parameters external to the media handler.
     * @param  array &$handlerParams Associative array of media handler parameters
     * @param  string|bool &$time
     * @param  string &$res Final HTML output
     * @return [type]                 [description]
     */
    public static function onImageBeforeProduceHTML (&$dummy, &$title, &$file, &$frameParams, &$handlerParams, &$time, &$res) {
        global $wgGloopFilesConfig;
        if ( $file ) {
            if ( $file->getMimeType() === "application/vnd.marmoset" ) {
                global $wgSVGMaxSize;
                $attr = [
                    'class' => [ 'gf-mview' ],
                    'style' => ""
                ];
                $inAttr = [
                    'class' => [ 'gf-mview-loader' ],
                    'style' => ""
                ];

                $page = $handlerParams['page'] ?? false;

                if ( isset( $handlerParams['width'] ) and (int) $handlerParams['width']  ) {
                    $width = (int) $handlerParams['width'];

                    if ( isset( $handlerParams['height'] ) and (int) $handlerParams['height']  ) {
                        $height = (int) $handlerParams['height'];
                    } else {
                        $height = (int) $wgGloopFilesConfig['height'] ?? 500;
                    }

                    if ( $width <= $height ) {
                        if ( $width > $wgSVGMaxSize ) {
                            $factor = $wgSVGMaxSize / $width;
                            $width = $wgSVGMaxSize;
                            $height = round( $height * $factor );
                        }
                    } else {
                        if ( $height > $wgSVGMaxSize ) {
                            $factor = $wgSVGMaxSize / $height;
                            $height = $wgSVGMaxSize;
                            $width = round( $width * $factor );
                        }
                    }

                    $attr['style'] = $attr['style'] . "width: {$width}px;";
                    $inAttr['style'] = $inAttr['style'] . "height: {$height}px;";
                } else {
                    $attr['class'][] = "gf-default-size";
                }

                if ( isset( $frameParams['class'] ) ) {
                    $attr['class'][] = $frameParams['class'];
                }

                if ( isset( $frameParams['align'] ) ) {
                    $attr['class'][] = "gf-frame-" . $frameParams['align'];
                } else {
                    $attr['class'][] = "gf-frame-center";
                }

                if ( isset( $frameParams['autostart'] ) ) {
                    $inAttr['data-mview-autostart'] = 'true';
                }

                if ( isset( $frameParams['title'] ) ) {
                  $attr['title'] = $frameParams['title'];
                }

                $pholder = "";
                if ( isset( $frameParams['alt'] ) ) {
                    $pholder = Html::element('span', [], $frameParams['alt']);
                } else {
                    $text = wfMessage( 'gloopfiles-mview-alt' )->params( $file->getTitle() )->parse();
                    $pholder = Html::rawelement('span', [], $text);
                }

                $inAttr['data-mview-file'] = $file->getCanonicalUrl();
                $inner = Html::rawElement('div', $inAttr, $pholder);

                $caption = "";
                if ( isset( $frameParams['caption'] ) ) {
                    $caption = Html::rawElement('figcaption', [], $frameParams['caption']);
                }

                $res = Html::rawElement('figure', $attr, $inner . $caption);
                return false;
            }
        }

        return true;
    }

}
