<?php
namespace FluidTYPO3\Flux\Tests\Unit\Backend;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Records;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Xml;
use FluidTYPO3\Flux\Tests\Unit\AbstractTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * PreviewTest
 */
class PreviewTest extends AbstractTestCase
{

    /**
     * Setup
     */
    public function setUp()
    {
        $configurationManager = $this->getMock('FluidTYPO3\Flux\Configuration\ConfigurationManager');
        $fluxService = $this->objectManager->get('FluidTYPO3\Flux\Service\FluxService');
        $fluxService->injectConfigurationManager($configurationManager);
        $GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetRows'), array(), '', false);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTgetRows')->willReturn(array());
        $tempFiles = (array) glob(GeneralUtility::getFileAbsFileName('typo3temp/flux-preview-*.tmp'));
        foreach ($tempFiles as $tempFile) {
            if (true === file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @test
     */
    public function canExecuteRenderer()
    {
        $caller = $this->getMock('TYPO3\CMS\Backend\View\PageLayoutView', array('attachAssets'), array(), '', false);
        $function = 'FluidTYPO3\Flux\Backend\Preview';
        $result = $this->callUserFunction($function, $caller);
        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function canGetPageTitleAndPidFromContentUid()
    {
        $className = 'FluidTYPO3\Flux\Backend\Preview';
        $instance = $this->getMock($className);
        $result = $this->callInaccessibleMethod($instance, 'getPageTitleAndPidFromContentUid', 1);
        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function stopsRenderingWhenProviderSaysStop()
    {
        $instance = $this->getMock('FluidTYPO3\Flux\Backend\Preview', array('createShortcutIcon', 'attachAssets'));
        $instance->expects($this->never())->method('createShortcutIcon');
        $configurationServiceMock = $this->getMock('FluidTYPO3\Flux\Service\FluxService', array('resolveConfigurationProviders'));
        $providerOne = $this->getMock('FluidTYPO3\Flux\Provider\ContentProvider', array('getPreview'));
        $providerOne->expects($this->once())->method('getPreview')->will($this->returnValue(array('test', 'test', false)));
        $providerTwo = $this->getMock('FluidTYPO3\Flux\Provider\ContentProvider', array('getPreview'));
        $providerTwo->expects($this->never())->method('getPreview');
        $configurationServiceMock->expects($this->once())->method('resolveConfigurationProviders')->will($this->returnValue(array($providerOne, $providerTwo)));
        ObjectAccess::setProperty($instance, 'configurationService', $configurationServiceMock, true);
        $header = 'test';
        $item = 'test';
        $record = Records::$contentRecordIsParentAndHasChildren;
        $draw = true;
        $this->setup();
        $instance->renderPreview($header, $item, $record, $draw);
    }

    /**
     * @param string $function
     * @param mixed $caller
     */
    protected function callUserFunction($function, $caller)
    {
        $drawItem = true;
        $headerContent = '';
        $itemContent = '';
        $row = Records::$contentRecordWithoutParentAndWithoutChildren;
        $row['pi_flexform'] = Xml::SIMPLE_FLEXFORM_SOURCE_DEFAULT_SHEET_ONE_FIELD;
        Core::registerConfigurationProvider('FluidTYPO3\Flux\Tests\Fixtures\Classes\DummyConfigurationProvider');
        $instance = $this->getMock($function, array('attachAssets'));
        $instance->preProcess($caller, $drawItem, $headerContent, $itemContent, $row);
        Core::unregisterConfigurationProvider('FluidTYPO3\Flux\Tests\Fixtures\Classes\DummyConfigurationProvider');
    }
}
