<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("class.ilCloudPluginGUI.php");

/**
 * Class ilCloudPluginInitGUI
 *
 * GUI Class for initialization of the object. Can be extended if needed.
 *
 * @author Timon Amstutz timon.amstutz@ilub.unibe.ch
 * @version $Id$
 * @extends ilCloudPluginGUI
 * @ingroup ModulesCloud
 */
class ilCloudPluginInitGUI extends ilCloudPluginGUI
{
    /**
     * @var ilObjCloudGUI
     */
    protected $gui_class = null;
    /**
     * @var bool
     */
    protected $perm_upload_items = false;
    /**
     * @var bool
     */
    protected $perm_create_folders = false;
    /**
     * @var bool
     */
    protected $perm_delete_files = false;
    /**
     * @var bool
     */
    protected $perm_delete_folders = false;
    /**
     * @var bool
     */
    protected $perm_download = false;
    /**
     * @var bool
     */
    protected $perm_files_visible = false;
    /**
     * @var bool
     */
    protected $perm_folders_visible = false;

    /**
     * @param \ilObjCloudGUI $gui_class
     */
    public function setGuiClass($gui_class)
    {
        $this->gui_class = $gui_class;
    }

    /**
     * @return \ilObjCloudGUI
     */
    public function getGuiClass()
    {
        return $this->gui_class;
    }

    /**
     * @param boolean $perm_create_folders
     */
    public function setPermCreateFolders($perm_create_folders)
    {
        $this->perm_create_folders = $perm_create_folders;
    }

    /**
     * @return boolean
     */
    public function getPermCreateFolders()
    {
        return $this->perm_create_folders;
    }

    /**
     * @param boolean $perm_delete_files
     */
    public function setPermDeleteFiles($perm_delete_files)
    {
        $this->perm_delete_files = $perm_delete_files;
    }

    /**
     * @return boolean
     */
    public function getPermDeleteFiles()
    {
        return $this->perm_delete_files;
    }

    /**
     * @param boolean $perm_delete_folders
     */
    public function setPermDeleteFolders($perm_delete_folders)
    {
        $this->perm_delete_folders = $perm_delete_folders;
    }

    /**
     * @return boolean
     */
    public function getPermDeleteFolders()
    {
        return $this->perm_delete_folders;
    }

    /**
     * @param boolean $perm_download
     */
    public function setPermDownload($perm_download)
    {
        $this->perm_download = $perm_download;
    }

    /**
     * @return boolean
     */
    public function getPermDownload()
    {
        return $this->perm_download;
    }

    /**
     * @param boolean $perm_files_visible
     */
    public function setPermFilesVisible($perm_files_visible)
    {
        $this->perm_files_visible = $perm_files_visible;
    }

    /**
     * @return boolean
     */
    public function getPermFilesVisible()
    {
        return $this->perm_files_visible;
    }

    /**
     * @param boolean $perm_folders_visible
     */
    public function setPermFoldersVisible($perm_folders_visible)
    {
        $this->perm_folders_visible = $perm_folders_visible;
    }

    /**
     * @return boolean
     */
    public function getPermFoldersVisible()
    {
        return $this->perm_folders_visible;
    }

    /**
     * @param boolean $perm_upload_items
     */
    public function setPermUploadItems($perm_upload_items)
    {
        $this->perm_upload_items = $perm_upload_items;
    }

    /**
     * @return boolean
     */
    public function getPermUploadItems()
    {
        return $this->perm_upload_items;
    }

    /**
     * @param \ilTemplate $tpl_file_tree
     */
    public function setTplFileTree($tpl_file_tree)
    {
        $this->tpl_file_tree = $tpl_file_tree;
    }

    /**
     * @return \ilTemplate
     */
    public function getTplFileTree()
    {
        return $this->tpl_file_tree;
    }



    /**
     * @var ilTemplate
     */
    protected $tpl_file_tree = null;

    public function initGUI(ilObjCloudGUI $gui_class, $perm_create_folder, $perm_upload_items , $perm_delete_files, $perm_delete_folders,$perm_download, $perm_files_visible, $perm_folders_visible)
    {
        global $ilTabs, $lng, $tpl;

        $ilTabs->activateTab("content");

        $this->setGuiClass($gui_class);
        $this->setPermUploadItems($perm_upload_items);
        $this->setPermCreateFolders($perm_create_folder);
        $this->setPermDeleteFiles($perm_delete_files);
        $this->setPermDeleteFolders($perm_delete_folders);
        $this->setPermDownload($perm_download);
        $this->setPermFilesVisible($perm_files_visible);
        $this->setPermFoldersVisible($perm_folders_visible);

        try
        {
            ilCloudConnector::checkServiceActive($this->getGUIClass()->object->getServiceName());
            $this->beforeInitGUI();

            //if($this->getPluginObject()->getAsyncDrawing())
            {
                $tpl->addJavaScript("./Modules/Cloud/js/ilCloudFileList.js");
                $tpl->addJavaScript("./Modules/Cloud/js/jquery.address.js");
                $tpl->addJavascript("./Services/UIComponent/AdvancedSelectionList/js/AdvancedSelectionList.js");
                $tpl->addCss("./Modules/Cloud/templates/css/cloud.css");

                include_once("./Services/YUI/classes/class.ilYuiUtil.php");
                ilYuiUtil::initConnection();


                $this->tpl_file_tree = new ilTemplate("tpl.cloud_file_tree.html", true, true, "Modules/Cloud");

                $file_tree = new ilCloudFileTree($this->getGUIClass()->object->getRootFolder(), $this->getGUIClass()->object->getRootId(), $this->getGUIClass()->object->getId(), $this->getGUIClass()->object->getServiceName());
                $file_tree->storeFileTreeToSession();

                $this->addToolbar($file_tree->getRootNode());

                $this->tpl_file_tree->setVariable("ASYNC_GET_BLOCK", json_encode($this->getGUIClass()->ctrl->getLinkTargetByClass("ilobjcloudgui", "asyncGetBlock", true)));
                $this->tpl_file_tree->setVariable("ASYNC_CREATE_FOLDER", json_encode($this->getGUIClass()->ctrl->getLinkTargetByClass("ilcloudplugincreatefoldergui", "asyncCreateFolder", true)));
                $this->tpl_file_tree->setVariable("ASYNC_UPLOAD_FILE", json_encode($this->getGUIClass()->ctrl->getLinkTargetByClass("ilcloudpluginuploadgui", "asyncUploadFile", true)));
                $this->tpl_file_tree->setVariable("ASYNC_DELETE_ITEM", json_encode($this->getGUIClass()->ctrl->getLinkTargetByClass("ilcloudplugindeletegui", "asyncDeleteItem", true)));
                $this->tpl_file_tree->setVariable("ROOT_ID", json_encode($file_tree->getRootNode()->getId()));
                $this->tpl_file_tree->setVariable("ROOT_PATH", json_encode($file_tree->getRootNode()->getPath()));
                if(isset($_POST["path"]))
                {
                    $this->tpl_file_tree->setVariable("CURRENT_PATH", json_encode($_POST["path"]));
                    $file_tree->updateFileTree($_POST["path"]);
                    $node = $file_tree->getNodeFromPath($_POST["path"]);
                    $this->tpl_file_tree->setVariable("CURRENT_ID", json_encode($node->getId()));
                }
                else
                {
                    $this->tpl_file_tree->setVariable("CURRENT_PATH", json_encode($file_tree->getRootNode()->getPath()));
                    $this->tpl_file_tree->setVariable("CURRENT_ID", json_encode($file_tree->getRootNode()->getID()));
                }
                $txt_max_file_size = $lng->txt("file_notice") . " " . ilCloudConnector::getPluginClass($this->getGUIClass()->object->getServiceName(), $this->getGUIClass()->object->getId())->getMaxFileSize() . " MB";
                $this->tpl_file_tree->setVariable("MAX_FILE_SIZE", json_encode($txt_max_file_size));
                $this->beforeSetContent();
                $tpl->setContent($this->tpl_file_tree->get());
                $tpl->setPermanentLink("cld", $this->getGuiClass()->object->getRefId(),"_path__endPath");
            }



            /**
            else
            {
                $file_tree = ilCloudFileTree::getFileTreeFromSession();
                if($_GET["current_path"] && $_GET["current_id"] && $file_tree && $file_tree->getId() == $this->getGUIClass()->object->getId())
                {
                    $path = $_GET["current_path"];
                    $id = $_GET["current_id"];

                }
                else
                {
                    $path = $gui_class->object->getRootFolder();
                    $id = $gui_class->object->getRootId();
                    ilCloudFileTree::clearFileTreeSession();
                    $file_tree = new ilCloudFileTree($this->getGUIClass()->object->getRootFolder(), $this->getGUIClass()->object->getRootId(), $this->getGUIClass()->object->getId(), $this->getGUIClass()->object->getServiceName());
                }


                $file_tree->updateFileTree($path);
                $file_tree_gui = ilCloudConnector::getFileTreeGUIClass($this->getService(), $file_tree);
                $this->content = $file_tree_gui->getFolderHtml($this->getGuiClass(), $id, $this->getPermDeleteFiles(), $this->getPermDeleteFolders(), $this->getPermDownload(), $this->getPermFilesVisible(), $this->getPermCreateFolders());
                $tpl->setContent($this->content);
            }**/
            $this->afterInitGUI();


        } catch (Exception $e)
        {
            if($e->getCode() == ilCloudException::AUTHENTIFICATION_FAILED)
            {
                $this->getGUIClass()->object->setAuthComplete(false);
                $this->getGUIClass()->object->doUpdate();


            }
            ilUtil::sendFailure($e->getMessage());
        }
    }

    function addToolbar($root_node)
    {
        global $lng, $ilToolbar, $tpl;

        $create_list_gui = ilCloudConnector::getItemCreationListGUIClass($this->getService());

        $list_gui_html = $create_list_gui->getGroupedListItemsHTML($this->getPermUploadItems(), $this->getPermCreateFolders());

        if($list_gui_html)
        {
            // toolbar
            $ov_id         = "il_add_new_cld_item_v";
            $ov_trigger_id = $ov_id ."_tr";
            $toolbar_locator = new ilLocatorGUI();
            $toolbar_locator->addItem($this->getGuiClass()->object->getTitle(), ilCloudPluginFileTreeGUI::getLinkToFolder($root_node));
            $ilToolbar->setId('xcld_toolbar');
            $ilToolbar->addText("<div class='xcld_locator'>". $toolbar_locator->getHtml()."</div>");
            $ilToolbar->addSeparator();
            $ilToolbar->addButton($lng->txt("cld_add_new_item"), "#", "", "", "", $ov_trigger_id, 'submit emphsubmit');
            include_once "Services/UIComponent/Overlay/classes/class.ilOverlayGUI.php";
            $ov = new ilOverlayGUI($ov_id);
            $ov->add();
            $ov->addTrigger($ov_trigger_id, "click", $ov_trigger_id, false, "tl", "tr");
            $tpl->setVariable("SELECT_OBJTYPE_REPOS", '<div id="' . $ov_id . '" class="ilOverlay ilNoDisplay">'.$list_gui_html.'</div>');
        }
    }

    public function beforeInitGUI(){}
    public function beforeSetContent(){}
    public function afterInitGUI(){}
}
?>