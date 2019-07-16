<?php
namespace Slub\Dfgviewer\Plugins\Sru;

/***************************************************************
*  Copyright notice
*
*  (c) 2014 Alexander Bigga <alexander.bigga@slub-dresden.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use \tx_dlf_plugin;
use \TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'DFG-Viewer: SRU Client' for the 'dfgviewer' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @copyright	Copyright (c) 2014, Alexander Bigga, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dfgviewer
 * @access	public
 */
class Sru extends tx_dlf_plugin {

	public $extKey = 'dfgviewer';

	public $scriptRelPath = 'Classes/Plugins/Sru/Sru.php';

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if required variables are not set.
			return $content;

		}

		// Get digital provenance information.
		$digiProv = $this->doc->mets->xpath('//mets:amdSec/mets:digiprovMD/mets:mdWrap[@OTHERMDTYPE="DVLINKS"]/mets:xmlData');

		if ($digiProv) {

			$links = $digiProv[0]->children('http://dfg-viewer.de/')->links;

			// if no children found with given namespace, skip the following section
			if ($links) {

				if ($links->sru) {

					$sruLink = htmlspecialchars(trim((string) $links->sru));

				}

			}

		}

		if (empty($sruLink)) {

			// Quit without doing anything if required variables are not set.
			return $content;

		}

        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

		// Load template file.
		if (!empty($this->conf['templateFile'])) {
			$this->template = $templateService->getSubpart($GLOBALS['TSFE']->tmpl->getFileName($this->conf['templateFile']), '###TEMPLATE###');
		} else {
			$this->template = $templateService->getSubpart($GLOBALS['TSFE']->tmpl->getFileName('EXT:dfgviewer/Resources/Private/Templates/Plugins/Dfgviewer/Sru.tmpl'), '###TEMPLATE###');
		}

		$this->addSearchFormJS();

		$this->addSruResultsJS();

		// Configure @action URL for form.
		$linkConf = array (
			'parameter' => $GLOBALS['TSFE']->id,
			'forceAbsoluteUrl' => 1
		);

		// Fill markers.
		$markerArray = array (
			'###ACTION_URL###' => $this->cObj->typoLink_URL($linkConf),
			'###LABEL_QUERY###' => $this->pi_getLL('label.query'),
			'###LABEL_DELETE_SEARCH###' => $this->pi_getLL('label.delete_search'),
			'###LABEL_LOADING###' => $this->pi_getLL('label.loading'),
			'###SRU_URL###' => $sruLink,
			'###LANG_ID###' => $this->LLkey,
			'###LABEL_SUBMIT###' => $this->pi_getLL('label.submit'),
			'###FIELD_QUERY###' => $this->prefixId.'[query]',
			'###QUERY###' => htmlspecialchars($lastQuery),
			'###CURRENT_DOCUMENT###' => $this->doc->location,
		);

		// Display search form.
		$content .= $templateService->substituteSubpart($templateService->substituteMarkerArray($this->template, $markerArray), '###EXT_SEARCH_ENTRY###', $extendedSearch);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Adds the JS files necessary for search form
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function addSearchFormJS() {

		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_sru'] = '<script type="text/javascript" src="'.ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Private/Javascript/tx_dfgviewer_sru.js"></script>';

	}

	/**
	 * Adds SRU Search result javascript
	 *
	 * @access	protected
	 *
	 * @return	string		Viewer script tags ready for output
	 */

	protected function addSruResultsJS() {


		if (!empty($this->piVars['highlight']) && !empty($this->piVars['origimage'])) {

			$highlight = unserialize(urldecode($this->piVars['highlight']));
			$origImage = $this->piVars['origimage'];

			// Add SRU Results if any
			$javascriptFooter = '
			<script type="text/javascript">$(window).load(function(){';

			foreach ($highlight as $field) {
				$javascriptFooter .= 'tx_dlf_viewer.addHighlightField(['.$field.'],'.$origImage.');';
			}

			$javascriptFooter .= '
			})</script>';

			$GLOBALS['TSFE']->additionalFooterData['tx-dfgviewer-footer'] .= $javascriptFooter;
		}

	}

}
