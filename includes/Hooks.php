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

namespace MediaWiki\Extensions\GloopFiles;

use MediaWiki\Language\Language;
use MediaWiki\MediaWikiServices;

class Hooks {

    public static function setup(): void {
        global $wgFileExtensions, $wgGloopFilesEnableUploads;
        if ( $wgGloopFilesEnableUploads === true ) {
            $wgFileExtensions[] = 'mview';
        }
    }

    /**
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/MimeMagicInit
     * @param  MimeAnalyzer $mime
     * @return null
     */
    public function onMimeMagicInit( $mime ) {
        $mime->addExtraTypes( 'application/vnd.marmoset mview' );
        $mime->addExtraTypes( 'application/sla mview' );
        $mime->addExtraTypes( 'application/octet-stream mview' );
        $mime->addExtraInfo( 'application/vnd.marmoset application/sla application/octet-stream [3D]' );
        return true;
    }

    /**
     * @param ImagePage $imagePage the imagepage that is being rendered
     * @param OutputPage $out the output for this imagepage
     * @return bool
     */
    public function onImageOpenShowImageInlineBefore( \ImagePage $imagePage, \OutputPage $out ) {
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
    public function onImagePageFileHistoryLine( $imagePage, $file, &$line, &$css ) {
        $out = $imagePage->getContext()->getOutput();
        return self::onImagePageHooks( $file, $out );
    }

    /**
     * @param File $file the file that is being rendered
     * @param OutputPage $out the output to which this file is being rendered
     * @return bool
     */
    private function onImagePageHooks( $file, $out ) {
        $handler = $file->getHandler();
        if ( $handler !== false && $handler instanceof MarmosetHandler ) {
            $out->addModules( ['ext.gloopfiles.mviewer'] );
        }
        return true;
    }

    /**
     * Add JavaScript and CSS for special pages that may include mview
     * files but which will not fire the parser hook.
     *
     * FIXME: There ought to be a better interface for determining whether the
     * page is liable to contain mview files.
     *
     * @param OutputPage $out
     * @param Skin $sk
     * @return bool
     */
    // public function onBeforePageDisplay ( \OutputPage $out, \Skin $sk ) {
    public function onBeforePageDisplay ( $out, $sk ) {
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
            $out->addModules( 'ext.gloopfiles.mviewer' );
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
    public function onRejectParserCacheValue( $parserOutput, $wikiPage, $parserOptions ) {
        if ( $parserOutput->getExtensionData( 'mw_ext_GF_hasMarmoset' ) &&
            !in_array( 'ext.gloopfiles.mviewer', $parserOutput->getModules() ) ) {
            return false;
        }
        return true;
    }

}
