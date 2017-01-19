<?php
namespace T4u\Payfull\Controller\Adminhtml\Payfullhistory;

use Magento\Backend\App\Action;

/**
 * Class MassDelete
 */
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $historyIds = $this->getRequest()->getParam('history');
        if (!is_array($historyIds) || empty($historyIds)) {
            $this->messageManager->addError(__('Please select tranaction(s).'));
        } else {
            try {
                foreach ($historyIds as $postId) {
                    $post = $this->_objectManager->get('T4u\Payfull\Model\History')->load($postId);
                    $post->delete();
                }
                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been deleted.', count($historyIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $this->resultRedirectFactory->create()->setPath('payfull/*/index');
    }
}
