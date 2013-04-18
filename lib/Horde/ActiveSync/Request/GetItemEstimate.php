<?php
/**
 * Horde_ActiveSync_Request_GetItemEstimate::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle GetItemEstimate requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Request_GetItemEstimate extends Horde_ActiveSync_Request_Base
{
    /** Status Codes **/
    const STATUS_SUCCESS    = 1;
    const STATUS_INVALIDCOL = 2;
    const STATUS_NOTPRIMED  = 3;
    const STATUS_KEYMISM    = 4;

    /* Request tag constants */
    const GETITEMESTIMATE = 'GetItemEstimate:GetItemEstimate';
    const VERSION         = 'GetItemEstimate:Version';
    const FOLDERS         = 'GetItemEstimate:Folders';
    const FOLDER          = 'GetItemEstimate:Folder';
    const FOLDERTYPE      = 'GetItemEstimate:FolderType';
    const FOLDERID        = 'GetItemEstimate:FolderId';
    const DATETIME        = 'GetItemEstimate:DateTime';
    const ESTIMATE        = 'GetItemEstimate:Estimate';
    const RESPONSE        = 'GetItemEstimate:Response';
    const STATUS          = 'GetItemEstimate:Status';

    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            '[%s] Handling GETITEMESTIMATE command.',
            $this->_device->id)
        );
        if (!$this->checkPolicyKey($this->_activeSync->getPolicyKey(), self::GETITEMESTIMATE)) {
            return true;
        }

        $status = array();
        $gStatus = self::STATUS_SUCCESS;

        $collections = array();
        if (!$this->_decoder->getElementStartTag(self::GETITEMESTIMATE) ||
            !$this->_decoder->getElementStartTag(self::FOLDERS)) {
            return false;
        }

        while ($this->_decoder->getElementStartTag(self::FOLDER)) {
            $options = array();
            $cStatus = self::STATUS_SUCCESS;
            while (($type = ($this->_decoder->getElementStartTag(self::FOLDERTYPE) ? self::FOLDERTYPE :
                            ($this->_decoder->getElementStartTag(self::FOLDERID) ? self::FOLDERID :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FILTERTYPE) ? Horde_ActiveSync::SYNC_FILTERTYPE :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SYNCKEY) ? Horde_ActiveSync::SYNC_SYNCKEY :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_CONVERSATIONMODE) ? Horde_ActiveSync::SYNC_CONVERSATIONMODE :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_OPTIONS) ? Horde_ActiveSync::SYNC_OPTIONS :
                            -1))))))) != -1) {
                switch ($type) {
                case self::FOLDERTYPE:
                    $class = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                    break;
                case self::FOLDERID:
                    $collectionid = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                    break;
                case Horde_ActiveSync::SYNC_CONVERSATIONMODE:
                    $conversationmode = $this->_decoder->getElementContent();
                    if ($conversationmode !== false && !$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol Error');
                    } elseif ($conversationmode === false) {
                        $conversationmode = true;
                    }
                    break;
                case Horde_ActiveSync::SYNC_FILTERTYPE:
                    $filtertype = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                    break;
                case Horde_ActiveSync::SYNC_SYNCKEY:
                    $synckey = $this->_decoder->getElementContent();
                    if (empty($synckey)) {
                        $cStatus = self::STATUS_NOTPRIMED;
                    }
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                    break;
                case Horde_ActiveSync::SYNC_OPTIONS:
                    // EAS > 12.1 only.
                    while (1) {
                        $firstOption = true;
                        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERTYPE)) {
                            $class = $this->_decoder->getElementContent();
                            if (!$this->_decoder->getElementEndTag()) {
                                throw new Horde_ActiveSync_Exception('Protocol Error');
                            }
                        } elseif ($firstOption) {
                            // Default options?
                        }
                        $firstOption = false;

                        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FILTERTYPE)) {
                            // Set filtertype? self::$decoder->getElementContent());
                            $filtertype = $this->_decoder->getElementContent();
                            if (!$this->_decoder->getElementEndTag()) {
                                throw new Horde_ActiveSync_Exception('Protocol Error');
                            }
                        }
                        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_WINDOWSIZE)) {
                            // Setwindowsize ($maxitems = self::$decoder->getElementContent());
                            if (!$this->_decoder->getElementEndTag()) {
                                throw new Horde_ActiveSync_Exception('Protocol Error');
                            }
                        }

                        // Only supported for 'RI' searches - which we don't support
                        // need to parse it though to avoid wbxml errors.
                        if ($this->_decoder->getELementStartTag(Horde_ActiveSync::SYNC_MAXITEMS)) {
                            if ($collectionid != 'RI') {
                                $gStatus = self::STATUS_INVALIDCOL;
                            }
                            if (!$this->_decoder->getElementEndTag()) {
                                throw new Horde_ActiveSync_Exception('Protocol Error');
                            }
                        }

                        $elm = $this->_decoder->peek();
                        if ($elm[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                            $this->_decoder->getElementEndTag();
                            break;
                        }
                    }
                }
            }
            // End the FOLDER element
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            // Build the collection array
            $collection = array();
            $collection['synckey'] = $synckey;
            $collection['filtertype'] = $filtertype;
            $collection['id'] = $collectionid;
            $collection['conversationmode'] = isset($conversationmode) ? $conversationmode : false;
            $status[$collection['id']] = $cStatus;
            if (!empty($class)) {
                $collection['class'] = $class;
            } else {
                $needCache = true;
            }
            $collections[] = $collection;
        }

        if (!empty($needCache)) {
            $syncCache = new Horde_ActiveSync_SyncCache(
                $this->_stateDriver,
                $this->_device->id,
                $this->_device->user,
                $this->_logger
            );
            $syncCache->validateCollectionsFromCache($collections);
        }

        // End Folders
        $this->_decoder->getElementEndTag();

        // End GETITEMESTIMATE
        $this->_decoder->getElementEndTag();

        $results = array();
        foreach ($collections as $collection) {
            if ($status[$collection['id']] == self::STATUS_SUCCESS) {
                try {
                    $this->_stateDriver->loadState(
                        $collection,
                        $collection['synckey'],
                        Horde_ActiveSync::REQUEST_TYPE_SYNC,
                        $collection['id']
                    );
                    $sync = $this->_activeSync->getSyncObject();
                    $sync->init($this->_stateDriver, null, $collection);
                    $results[$collection['id']] = $sync->getChangeCount();
                } catch (Horde_ActiveSync_Exception_StateGone $e) {
                    $this->_logger->err('State Gone. Terminating GETITEMESTIMATE');
                    $status[$collection['id']] = $gStatus = self::STATUS_KEYMISM;
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err('Unknown error in GETITEMESTIMATE');
                    $status[$collection['id']] = $gStatus = self::STATUS_KEYMISM;
                }
            }
        }

        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::GETITEMESTIMATE);
        $this->_encoder->startTag(self::STATUS);
        $this->_encoder->content($gStatus);
        $this->_encoder->endTag();
        foreach ($collections as $collection) {
            $this->_encoder->startTag(self::RESPONSE);
            $this->_encoder->startTag(self::STATUS);
            $this->_encoder->content($status[$collection['id']]);
            $this->_encoder->endTag();
            $this->_encoder->startTag(self::FOLDER);
            if (!empty($collection['class'])) {
                $this->_encoder->startTag(self::FOLDERTYPE);
                $this->_encoder->content($collection['class']);
                $this->_encoder->endTag();
            }
            $this->_encoder->startTag(self::FOLDERID);
            $this->_encoder->content($collection['id']);
            $this->_encoder->endTag();
            if ($status[$collection['id']] == self::STATUS_SUCCESS) {
                $this->_encoder->startTag(self::ESTIMATE);
                $this->_encoder->content($results[$collection['id']]);
                $this->_encoder->endTag();
            }
            $this->_encoder->endTag();
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

}
