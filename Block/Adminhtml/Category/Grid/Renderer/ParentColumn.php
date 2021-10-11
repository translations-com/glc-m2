<?php


namespace TransPerfect\GlobalLink\Block\Adminhtml\Category\Grid\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use \Magento\Catalog\Api\CategoryRepositoryInterface;

class ParentColumn extends AbstractRenderer
{
    protected $categoryRepository;

    /**
     * constructor
     *
     * @param \Magento\Backend\Block\Context             $context
     * @param array                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context, $data);
    }

    /**
     * Render action
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $storeId = $this->getRequest()->getParam('store');

        try {
            $category = $this->categoryRepository->get($row['parent_id'], $storeId);
        } catch (NoSuchEntityException $e) {
            return $row['parent_id'];
        }
        return $category->getName();
    }
}
