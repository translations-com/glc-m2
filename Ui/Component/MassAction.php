<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Ui\Component;

use Magento\Ui\Component\MassAction as BaseMassAction;
use \TransPerfect\GlobalLink\Model\ResourceModel\Queue\ItemFactory as ItemResourceFactory;

/**
 * Class MassAction
 *
 * @package TransPerfect\GlobalLink\Ui\Component
 */
class MassAction extends BaseMassAction
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\ItemFactory
     */
    protected $itemResourceFactory;

    /**
     * MassAction constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\App\Request\Http                          $request
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\ItemFactory $itemResourceFactory
     * @param array                                                        $components
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\App\Request\Http $request,
        ItemResourceFactory $itemResourceFactory,
        $components,
        array $data = []
    ) {
        $this->request = $request;
        $this->itemResourceFactory = $itemResourceFactory;
        parent::__construct($context, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepare()
    {
        parent::prepare();

        $controller = $this->getRequest()->getControllerName();
        $allowedControllers = ['cms_page', 'block', 'product', 'page'];
        if (in_array($controller, $allowedControllers)) {
            $itemResource = $this->itemResourceFactory->create();
            switch ($controller) {
                case 'cms_page':
                case 'page':
                    $storeId = $itemResource->getUiGridStoreId('cms_page_listing');
                    $url = $this->getContext()->getUrl('translations/submission_cms_page/create', ['store' => $storeId]);
                    break;
                case 'block':
                    $storeId = $itemResource->getUiGridStoreId('cms_block_listing');
                    $url = $this->getContext()->getUrl('translations/submission_cms_block/create', ['store' => $storeId]);
                    break;
                case 'product':
                    $storeId = $itemResource->getUiGridStoreId('product_listing');
                    $url = $this->getContext()->getUrl('translations/submission_product/create', ['store' => $storeId]);
                    break;
                default:
                    $url = false;
            }
            if ($url) {
                $config = $this->getConfiguration();
                $config['actions'][] = [
                    'component' => "uiComponent",
                    "confirm" => [
                        "title" => __("Send for Translation"),
                        "message" => __("Do you really want to create a submission?")
                      ],
                    "type" => "submission",
                    "label" => __("Send for Translation"),
                    "url" => $url
                ];
                $this->setData('config', $config);
            }
        }
    }

    protected function getRequest()
    {
        return $this->request;
    }
}
