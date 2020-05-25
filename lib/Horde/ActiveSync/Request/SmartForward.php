<?php
/**
 * Horde_ActiveSync_Request_SmartForward::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *
 * @copyright 2009-2020 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * ActiveSync Handler for SmartForward requests. The device only sends the reply
 * text, along with the message uid and collection id (mailbox). The server is
 * responsible for appending the original text.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *
 * @copyright 2009-2020 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
 */
class Horde_ActiveSync_Request_SmartForward extends Horde_ActiveSync_Request_SendMail
{
    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        if ($this->_decoder->isWbxml()) {
            return $this->_handleWbxmlRequest();
        }

        $rfc822 = file_get_contents('php://input');
        $get = $this->_activeSync->getGetVars();
        if (empty($get['ItemId'])) {
            $orig = false;
        } else {
            $orig = $get['ItemId'];
        }
        if (empty($get['CollectionId'])) {
            $parent = false;
        } else {
            $parent = $this->_activeSync->getCollectionsObject()
                ->getBackendIdForFolderUid($get['CollectionId']);
        }

        try {
            return $this->_driver->sendMail($rfc822, $orig, false, $parent);
        } catch (Horde_Exception_NotFound $e) {
            $this->_logger->err($e->getMessage());
            $this->_handleError(
                Horde_ActiveSync_Status::ITEM_NOT_FOUND,
                Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SMARTFORWARD);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_handleError(
                Horde_ActiveSync_Status::MAIL_SUBMISSION_FAILED,
                Horde_ActiveSync_Message_SendMail::COMPOSEMAIL_SMARTFORWARD);
        }

        return true;
    }

}
