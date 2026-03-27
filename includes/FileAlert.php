<?php
/**
 * NovaDiscord - File-related hooks
 * This file is licensed under the MIT License; See LICENSE for full text.
 */

namespace MediaWiki\Extension\NovaDiscord;

use MediaWiki\Hook\FileDeleteCompleteHook;
use MediaWiki\Hook\FileUndeleteCompleteHook;
use MediaWiki\Hook\UploadCompleteHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\Spi as LoggerSpi;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\Utils\UrlUtils;

class FileAlert extends DiscordAlert implements UploadCompleteHook, FileDeleteCompleteHook, FileUndeleteCompleteHook {
    private UserFactory $userFactory;

    public function __construct(HttpRequestFactory $httpFactory,
                                RevisionLookup $revLookup,
                                TitleFactory $titleFactory,
                                UrlUtils $urlUtils,
                                UserFactory $userFactory,
                                NovaDiscordConfig $novaConfig,
                                LoggerSpi $loggerSpi) {
        $this->userFactory = $userFactory;
        parent::__construct($httpFactory, $revLookup, $titleFactory, $urlUtils, $novaConfig, $loggerSpi);
    }

    /**
     * Called when a file upload is complete
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete
     */
    public function onUploadComplete($uploadBase) {
        $hookName = 'UploadComplete';

        $lf = $uploadBase->getLocalFile();
        $user = $this->userFactory->newFromUserIdentity($lf->getUploader());

        if (!$this->isEnabled($hookName, NS_FILE, $user)) {
            return;
        }

        $comment = $lf->getDescription();
        $isNewRevision = count($lf->getHistory()) > 0;

        $msg = wfMessage(
            'discord-uploadcomplete',
            $this->formatUserLink($user),
            ($isNewRevision ? wfMessage('discord-uploadnewver')->inContentLanguage()->text() : ''),
            $this->formatMarkdownLink($lf->getName(), $lf->getTitle()->getCanonicalURL()),
            $this->formatMessage($comment),
            $this->formatBytes($lf->getSize()),
            $lf->getWidth(),
            $lf->getHeight(),
            $lf->getMimeType()
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, (int)wfTimestamp(TS_UNIX, $lf->getTimestamp()));
    }

    /**
     * Called when a file is deleted
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileDeleteComplete
     */
    public function onFileDeleteComplete($file, $oldimage, $article, $user, $reason) {
        $hookName = 'FileDeleteComplete';

        if (!$this->isEnabled($hookName, NS_FILE, $user)) {
            return;
        }

        if ($article) {
            // Entire page was deleted, onPageDeleteComplete will handle this
            return;
        }

        $msg = wfMessage(
            'discord-filedeletecomplete',
            $this->formatUserLink($user),
            $this->formatMarkdownLink($file->getName(), $file->getTitle()->getCanonicalURL()),
            $this->formatMessage($reason)
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, (int)wfTimestamp(TS_UNIX, $file->getTimestamp()));
    }

    /**
     * Called when a file is restored
     * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUndeleteComplete
     */
    public function onFileUndeleteComplete($title, $fileVersions, $user, $reason) {
        $hookName = 'FileUndeleteComplete';

        if (!$this->isEnabled($hookName, NS_FILE, $user)) {
            return;
        }

        $msg = wfMessage(
            'discord-fileundeletecomplete',
            $this->formatUserLink($user),
            $this->formatMarkdownLink($title, $title->getCanonicalURL()),
            $this->formatMessage($reason)
        )->inContentLanguage()->plain();
        $this->sendAlert($hookName, $msg, time());
    }
}
