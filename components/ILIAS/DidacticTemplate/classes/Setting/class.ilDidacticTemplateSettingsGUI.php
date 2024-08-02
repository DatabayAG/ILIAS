<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Data\Order;
use Psr\Http\Message\RequestInterface;
use ILIAS\DI\Container;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Refinery\Factory;
use ILIAS\FileUpload\FileUpload;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\Export\ImportStatus\ilCollection as ilImportStatusCollection;
use ILIAS\Export\ImportStatus\ilFactory as ilImportStatusFactory;
use ILIAS\Export\ImportStatus\StatusType as ImportStatusType;
use ILIAS\MetaData\Services\ServicesInterface as LOMServices;

/**
 * Settings for a single didactic template
 * @author            Stefan Meyer <meyer@leifos.com>
 * @ingroup           ServicesDidacticTemplate
 * @ilCtrl_IsCalledBy ilDidacticTemplateSettingsGUI: ilObjRoleFolderGUI
 * @ilCtrl_Calls      ilDidacticTemplateSettingsGUI: ilMultilingualismGUI, ilPropertyFormGUI
 */
class ilDidacticTemplateSettingsGUI
{
    protected UIRenderer $renderer;
    protected UIFactory $ui_factory;
    private ilLogger $logger;
    private ?ilDidacticTemplateSetting $setting = null;
    private Container $dic;
    private ilLanguage $lng;
    private ilRbacSystem $rbacsystem;
    private ilCtrl $ctrl;
    private ilAccessHandler $access;
    private ilToolbarGUI $toolbar;
    private ilObjectDefinition $objDefinition;
    private GlobalHttpState $http;
    private Factory $refinery;
    private ilGlobalTemplateInterface $tpl;
    private ilTabsGUI $tabs;
    private FileUpload $upload;
    private LOMServices $lom_services;

    private int $ref_id;

    public function __construct(ilObjectGUI $a_parent_obj)
    {
        global $DIC;
        $this->lng = $DIC->language();
        $this->rbacsystem = $DIC->rbac()->system();
        $this->ctrl = $DIC->ctrl();
        $this->objDefinition = $DIC['objDefinition'];
        $this->access = $DIC->access();
        $this->toolbar = $DIC->toolbar();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->logger = $DIC->logger()->otpl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
        $this->upload = $DIC->upload();
        $this->renderer = $DIC->ui()->renderer();
        $this->ui_factory = $DIC->ui()->factory();
        $this->lom_services = $DIC->learningObjectMetadata();
    }

    protected function initReferenceFromRequest(): void
    {
        if ($this->http->wrapper()->query()->has('ref_id')) {
            $this->ref_id = $this->http->wrapper()->query()->retrieve(
                'ref_id',
                $this->refinery->kindlyTo()->int()
            );
        }
    }

    /**
     * transforms selected tpls from post to SplFixedArray
     */
    protected function initTemplatesFromRequest(): SplFixedArray
    {
        if ($this->http->wrapper()->query()->has('tpls')) {
            return SplFixedArray::fromArray(explode(
                ',',
                $this->http->wrapper()->query()->retrieve('tpls', $this->refinery->custom()->transformation(fn($v) => $v))
            ));
        }
        return new SplFixedArray(0);
    }

    protected function initTemplateFromRequest(): ?ilDidacticTemplateSetting
    {
        if ($this->http->wrapper()->query()->has('tplid')) {
            $tpl_id = $this->http->wrapper()->query()->retrieve(
                'tplid',
                $this->refinery->kindlyTo()->int()
            );
            return $this->setting = new ilDidacticTemplateSetting($tpl_id);
        }

        return null;
    }

    public function executeCommand(): string
    {
        $this->initReferenceFromRequest();

        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case "ilpropertyformgui":
                $setting = $this->initTemplateFromRequest();
                if (!$setting instanceof ilDidacticTemplateSetting) {
                    $setting = new ilDidacticTemplateSetting();
                }
                $form = $this->initEditTemplate($setting);
                $this->ctrl->forwardCommand($form);
                // no break
            case 'ilmultilingualismgui':
                $setting = $this->initTemplateFromRequest();
                if (
                    !$this->access->checkAccess('write', '', $this->ref_id) ||
                    !$setting instanceof ilDidacticTemplateSetting ||
                    $setting->isAutoGenerated()) {
                    $this->ctrl->redirect($this, "overview");
                }
                $this->setEditTabs("settings_trans");
                $transgui = new ilMultilingualismGUI($this->setting->getId(), 'dtpl');
                $defaultl = $this->setting->getTranslationObject()->getDefaultLanguage();
                $transgui->setStartValues(
                    $this->setting->getPresentationTitle($defaultl),
                    $this->setting->getPresentationDescription($defaultl)
                );
                $this->ctrl->forwardCommand($transgui);
                break;
            default:
                if (!$cmd) {
                    $cmd = 'overview';
                }
                $this->$cmd();
                break;
        }
        return '';
    }

    protected function handleTableActions(): void
    {
        $query = $this->http->wrapper()->query();
        if (!$query->has('didactic_template_table_action')) {
            return;
        }
        $action = $query->retrieve('didactic_template_table_action', $this->refinery->to()->string());

        $ids = $this->http->wrapper()->query()->retrieve(
            'didactic_template_template_ids',
            $this->refinery->custom()->transformation(function ($q_ids) {
                if (is_array($q_ids)) {
                    return $q_ids;
                }
                return strlen($q_ids) > 0 ? explode(',', $q_ids) : [];
            })
        );

        switch ($action) {
            case 'editTemplate':
                $this->editTemplate((int) $ids[0]);
                break;
            case 'exportTemplate':
                $this->exportTemplate((int) $ids[0]);
                break;
            case 'copyTemplate':
                $this->copyTemplate((int) $ids[0]);
                break;
            case 'activateTemplates':
                $this->activateTemplates($ids);
                break;
            case 'deactivateTemplates':
                $this->deactivateTemplates($ids);
                break;
            case 'confirmDelete':
                $this->confirmDelete($ids);
                break;
        }
    }

    protected function overview(): void
    {
        if ($this->rbacsystem->checkAccess('write', $this->ref_id)) {
            $this->toolbar->addButton(
                $this->lng->txt('didactic_import_btn'),
                $this->ctrl->getLinkTarget($this, 'showImportForm')
            );
        }
        $filter = new ilDidacticTemplateSettingsTableFilter($this->ctrl->getFormAction($this, 'overview'));
        $filter->init();

        $data_retrieval = new ilDidacticTemplateSettingsTableDataRetrieval(
            $filter,
            $this->lng,
            $this->ui_factory,
            $this->renderer
        );

        $table = new ilDidacticTemplateSettingsTableGUI($this, $this->ref_id);
        $this->tpl->setContent(
            $filter->render() . $table->getHTML($data_retrieval)
        );
    }

    public function applyFilter(): void
    {
        $this->overview();
    }

    public function resetFilter(): void
    {
        $this->overview();
    }

    protected function showImportForm(ilPropertyFormGUI $form = null): void
    {
        $setting = $this->initTemplateFromRequest();
        if ($setting instanceof ilDidacticTemplateSetting) {
            $this->setEditTabs('import');
        } else {
            $this->tabs->clearTargets();
            $this->tabs->setBackTarget(
                $this->lng->txt('didactic_back_to_overview'),
                $this->ctrl->getLinkTarget($this, 'overview')
            );
        }

        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->createImportForm();
        }
        $this->tpl->setContent($form->getHTML());
    }

    protected function createImportForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setShowTopButtons(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('didactic_import_table_title'));
        $form->addCommandButton('importTemplate', $this->lng->txt('import'));
        $form->addCommandButton('overview', $this->lng->txt('cancel'));

        $file = new ilFileInputGUI($this->lng->txt('import_file'), 'file');
        $file->setSuffixes(['xml']);
        $file->setRequired(true);
        $form->addItem($file);

        $icon = new ilImageFileInputGUI($this->lng->txt('icon'), 'icon');
        $icon->setAllowDeletion(false);
        $icon->setSuffixes(['svg']);
        $icon->setInfo($this->lng->txt('didactic_icon_info'));
        $form->addItem($icon);

        $created = true;

        return $form;
    }

    protected function checkInput(
        ilPropertyFormGUI $form,
        ilDidacticTemplateImport $import
    ): ilImportStatusCollection {
        $status = new ilImportStatusFactory();

        if (!$form->checkInput()) {
            $form->setValuesByPost();
            return $status->collection();
        }

        $file = $form->getInput('file');
        $tmp = ilFileUtils::ilTempnam() . '.xml';

        // move uploaded file
        ilFileUtils::moveUploadedFile(
            $file['tmp_name'],
            $file['name'],
            $tmp
        );
        $import->setInputFile($tmp);
        $statuses = $import->validateImportFile();

        if (!$statuses->hasStatusType(ImportStatusType::FAILED)) {
            $statuses = $statuses->withAddedStatus($status->handler()
                ->withType(ImportStatusType::SUCCESS)
                ->withContent($status->content()->builder()->string()->withString('')));
        }
        return $statuses;
    }

    protected function importTemplate(): void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }

        $setting = $this->initTemplateFromRequest();
        if ($setting instanceof ilDidacticTemplateSetting) {
            $form = $this->editImportForm();
        } else {
            $form = $this->createImportForm();
        }

        $import = new ilDidacticTemplateImport(ilDidacticTemplateImport::IMPORT_FILE);
        $statuses = $this->checkInput($form, $import);

        if (!$statuses->hasStatusType(ImportStatusType::SUCCESS)) {
            $error_msg = ($statuses->getCollectionOfAllByType(ImportStatusType::FAILED)->count() > 0)
                ? $statuses
                ->withNumberingEnabled(true)
                ->toString(ImportStatusType::FAILED)
                : '';
            $this->tpl->setOnScreenMessage(
                'failure',
                $this->lng->txt('didactic_import_failed') . $error_msg
            );
            if ($setting instanceof ilDidacticTemplateSetting) {
                $this->showEditImportForm($form);
            } else {
                $this->showImportForm($form);
            }
            return;
        }

        try {
            $settings = $import->import();
            if ($setting instanceof ilDidacticTemplateSetting) {
                $this->editImport($settings);
            } elseif ($settings->hasIconSupport($this->objDefinition)) {
                $settings->getIconHandler()->handleUpload($this->upload, $_FILES['icon']['tmp_name']);
            }
        } catch (ilDidacticTemplateImportException $e) {
            $this->logger->error('Import failed with message: ' . $e->getMessage());
            $this->tpl->setOnScreenMessage(
                'failure',
                $this->lng->txt('didactic_import_failed') . ': ' . $e->getMessage(),
                true
            );
            $this->ctrl->redirect($this, 'importTemplate');
        }

        $this->tpl->setOnScreenMessage(
            'success',
            $this->lng->txt('didactic_import_success'),
            true
        );

        if ($setting instanceof ilDidacticTemplateSetting) {
            $this->ctrl->redirect($this, 'editTemplate');
        } else {
            $this->ctrl->redirect($this, 'overview');
        }
    }

    protected function editTemplate(?int $template_id = null, ilPropertyFormGUI $form = null): void
    {
        $setting = null;
        if (is_null($template_id)) {
            $setting = $this->initTemplateFromRequest();
        } else {
            $setting = ($this->setting = new ilDidacticTemplateSetting($template_id));
            $this->ctrl->setParameter($this, 'tplid', $template_id);
        }
        if (!$setting instanceof ilDidacticTemplateSetting) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
        }
        $this->setEditTabs("edit");
        $this->ctrl->saveParameter($this, 'tplid');
        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->initEditTemplate($this->setting);
        }
        $this->tpl->setContent($form->getHTML());
    }

    protected function updateTemplate(): void
    {
        $setting = $this->initTemplateFromRequest();
        $this->ctrl->saveParameter($this, 'tplid');

        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }

        $form = $this->initEditTemplate($this->setting);

        if ($form->checkInput()) {
            $tmp_file = $_FILES['icon']['tmp_name'] ?? '';
            $upload_element = $form->getItemByPostVar('icon');
            if (
                ($tmp_file !== '' || ($tmp_file === '' && $this->setting->getIconIdentifier())) &&
                !$this->objDefinition->isContainer($form->getInput('type')) &&
                !$upload_element->getDeletionFlag()
            ) {
                $form->getItemByPostVar('icon')->setAlert($this->lng->txt('didactic_icon_error'));
                $this->handleUpdateFailure($form);
                return;
            }
            //change default entries if translation is active
            if (count($lang = $this->setting->getTranslationObject()->getLanguages())) {
                $this->setting->getTranslationObject()->setDefaultTitle($form->getInput('title'));
                $this->setting->getTranslationObject()->setDefaultDescription($form->getInput('description'));
                $this->setting->getTranslationObject()->save();
            }

            if (!$this->setting->isAutoGenerated()) {
                $this->setting->setTitle($form->getInput('title'));
                $this->setting->setDescription($form->getInput('description'));
            }

            $this->setting->setInfo($form->getInput('info'));
            $this->setting->enable((bool) $form->getInput('enable'));

            if (!$this->setting->isAutoGenerated()) {
                $this->setting->setAssignments([$form->getInput('type')]);
            }

            if ($form->getInput('local_template') && count($form->getInput('effective_from')) > 0) {
                $this->setting->setEffectiveFrom($form->getInput('effective_from'));
            } else {
                $this->setting->setEffectiveFrom([]);
            }

            $this->setting->setExclusive((bool) $form->getInput('exclusive_template'));

            $this->setting->update();

            $upload = $form->getItemByPostVar('icon');
            if ($upload->getDeletionFlag()) {
                $this->setting->getIconHandler()->delete();
            }
            $this->setting->getIconHandler()->handleUpload($this->upload, $_FILES['icon']['tmp_name']);
            $this->tpl->setOnScreenMessage('success', $this->lng->txt('settings_saved'), true);
            $this->ctrl->redirect($this, 'overview');
        }
        $this->handleUpdateFailure($form);
    }

    protected function handleUpdateFailure(ilPropertyFormGUI $form): void
    {
        $this->tpl->setOnScreenMessage('failure', $this->lng->txt('err_check_input'));
        $form->setValuesByPost();
        $this->editTemplate(null, $form);
    }

    protected function initEditTemplate(ilDidacticTemplateSetting $set): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setShowTopButtons(false);
        $form->setFormAction($this->ctrl->getFormAction($this, 'updateTemplate'));
        $form->setTitle($this->lng->txt('didactic_edit_tpl'));
        $form->addCommandButton('updateTemplate', $this->lng->txt('save'));
        $form->addCommandButton('overview', $this->lng->txt('cancel'));

        // title
        $title = new ilTextInputGUI($this->lng->txt('title'), 'title');
        $title->setSize(40);
        $title->setMaxLength(64);
        $title->setRequired(true);
        //use presentation title if autogenerated is set
        $title->setDisabled($set->isAutoGenerated());

        $def = [];
        if (!$set->isAutoGenerated()) {
            $trans = $set->getTranslations();
            $def = $trans[0]; // default

            if (count($trans) > 1) {
                $language = '';
                foreach ($this->lom_services->dataHelper()->getAllLanguages() as $lom_lang) {
                    if ($lom_lang->value() === ($def["lang_code"] ?? '')) {
                        $language = $lom_lang->presentableLabel();
                    }
                }
                $title->setInfo($this->lng->txt("language") . ": " . $language .
                    ' <a href="' . $this->ctrl->getLinkTargetByClass("ilmultilingualismgui", "listTranslations") .
                    '">&raquo; ' . $this->lng->txt("more_translations") . '</a>');
            }
        }

        if ($set->isAutoGenerated()) {
            $title->setValue($set->getPresentationTitle());
        } elseif (isset($def['title'])) {
            $title->setValue($def["title"]);
        }

        $form->addItem($title);

        // desc
        $desc = new ilTextAreaInputGUI($this->lng->txt('description'), 'description');
        //use presentation title if autogenerated is set
        if ($set->isAutoGenerated()) {
            $desc->setValue($set->getPresentationDescription());
        } elseif (isset($def['description'])) {
            $desc->setValue($def["description"]);
        }
        $desc->setRows(3);
        $desc->setDisabled($set->isAutoGenerated());
        $form->addItem($desc);

        $icon = new ilImageFileInputGUI($this->lng->txt('didactic_icon'), 'icon');
        $icon->setImage($set->getIconHandler()->getAbsolutePath());
        $icon->setInfo($this->lng->txt('didactic_icon_info'));
        $icon->setAllowDeletion(true);
        $icon->setSuffixes(['svg']);
        $form->addItem($icon);

        // info
        $info = new ilTextAreaInputGUI($this->lng->txt('didactic_install_info'), 'info');
        $info->setValue($set->getInfo());
        $info->setRows(6);
        $form->addItem($info);

        //activate
        $enable = new ilCheckboxInputGUI($this->lng->txt('active'), 'enable');
        $enable->setChecked($set->isEnabled());
        $form->addItem($enable);

        // object type
        if (!$set->isAutoGenerated()) {
            $type = new ilSelectInputGUI($this->lng->txt('obj_type'), 'type');
            $type->setRequired(true);
            $type->setInfo($this->lng->txt('dtpl_obj_type_info'));
            $assigned = $set->getAssignments();
            $type->setValue($assigned[0] ?? '');
            $subs = $this->objDefinition->getSubObjectsRecursively('root', false);
            $options = [];
            foreach (array_merge($subs, ['fold' => 1]) as $obj => $null) {
                ilLoggerFactory::getLogger('root')->dump($null);
                if ($this->objDefinition->isPlugin($obj)) {
                    $options[$obj] = ilObjectPlugin::lookupTxtById($obj, "obj_" . $obj);
                } elseif ($this->objDefinition->isAllowedInRepository($obj)) {
                    $options[$obj] = $this->lng->txt('obj_' . $obj);
                }
            }
            asort($options);

            $type->setOptions($options);
            $form->addItem($type);

            $lokal_templates = new ilCheckboxInputGUI(
                $this->lng->txt("activate_local_didactic_template"),
                "local_template"
            );
            $lokal_templates->setChecked(count($set->getEffectiveFrom()) > 0);
            $lokal_templates->setInfo($this->lng->txt("activate_local_didactic_template_info"));

            //effective from (multinode)

            $effrom = new ilRepositorySelector2InputGUI($this->lng->txt("effective_form"), "effective_from", true);
            //$effrom->setMulti(true);
            $white_list = [];
            foreach ($this->objDefinition->getAllRepositoryTypes() as $type) {
                if ($this->objDefinition->isContainer($type)) {
                    $white_list[] = $type;
                }
            }
            $effrom->getExplorerGUI()->setTypeWhiteList($white_list);
            $effrom->setValue($set->getEffectiveFrom());

            $lokal_templates->addSubItem($effrom);
            $form->addItem($lokal_templates);

            $excl = new ilCheckboxInputGUI($this->lng->txt("activate_exclusive_template"), "exclusive_template");
            $excl->setInfo($this->lng->txt("activate_exclusive_template_info"));
            $excl->setChecked($set->isExclusive());

            $form->addItem($excl);
        }

        return $form;
    }

    protected function copyTemplate(int $template_id): void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }
        $setting = ($this->setting = new ilDidacticTemplateSetting($template_id));
        $copier = new ilDidacticTemplateCopier($setting->getId());
        $copier->start();
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('didactic_copy_suc_message'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    protected function exportTemplate(int $template_id): void
    {
        $setting = ($this->setting = new ilDidacticTemplateSetting($template_id));
        $writer = new ilDidacticTemplateXmlWriter($setting->getId());
        $writer->write();
        ilUtil::deliverData(
            $writer->xmlDumpMem(true),
            $writer->getSetting()->getTitle() . '.xml',
            'application/xml'
        );
    }

    /**
     * @param string[] $template_ids
     */
    protected function confirmDelete(array $template_ids): never
    {
        $this->ctrl->setParameterByClass(ilDidacticTemplateSettingsGUI::class, 'tpls', implode(',', $template_ids));
        $del_action = $this->ctrl->getLinkTarget($this, 'deleteTemplates');
        $this->ctrl->clearParameterByClass(ilDidacticTemplateSettingsGUI::class, 'tpls');
        $items = [];
        $tpls = ilDidacticTemplateSettings::getInstance();
        $tpls->readInactive();
        $templates = $tpls->getTemplates();
        foreach ($templates as $template) {
            foreach ($template_ids as $id) {
                if ((int) $id !== $template->getId()) {
                    continue;
                }
                $items[] = $this->ui_factory->modal()->interruptiveItem()->standard(
                    $id,
                    $template->getTitle()
                );
            }
        }
        echo($this->renderer->renderAsync([
            $this->ui_factory->modal()->interruptive(
                $this->lng->txt('delete'),
                $this->lng->txt('modal_confirm_deletion_text'),
                $del_action
            )
                ->withAffectedItems($items)
                ->withAdditionalOnLoadCode(static fn($id): string => "console.log('ASYNC JS');")
        ]));
        exit();
    }

    /**
     * @param string[] $template_ids
     */
    protected function deleteTemplates(): void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }
        $template_ids = $this->initTemplatesFromRequest();
        if (0 === count($template_ids) || $template_ids[0] === '') {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('select_one'), true);
            //$this->ctrl->redirect($this, 'overview');
            return;
        }
        foreach ($template_ids as $tplid) {
            $tpl = new ilDidacticTemplateSetting((int) $tplid);
            $tpl->delete();
        }
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('didactic_delete_msg'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    /**
     * @param string[] $template_ids
     */
    protected function activateTemplates(array $template_ids): void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }
        if (0 === count($template_ids)) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
            return;
        }
        foreach ($template_ids as $tplid) {
            $tpl = new ilDidacticTemplateSetting((int) $tplid);
            $tpl->enable(true);
            $tpl->update();
        }
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('didactic_activated_msg'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    /**
     * @param string[] $template_ids
     */
    protected function deactivateTemplates(array $template_ids): void
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->redirect($this, "overview");
        }
        if (0 === count($template_ids)) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('select_one'), true);
            $this->ctrl->redirect($this, 'overview');
        }
        foreach ($template_ids as $tplid) {
            $tpl = new ilDidacticTemplateSetting((int) $tplid);
            $tpl->enable(false);
            $tpl->update();
        }
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('didactic_deactivated_msg'), true);
        $this->ctrl->redirect($this, 'overview');
    }

    protected function setEditTabs(string $a_tab_active = "edit"): void
    {
        $this->lng->loadLanguageModule('obj');
        $this->tabs->clearTargets();
        $this->tabs->setBackTarget(
            $this->lng->txt('didactic_back_to_overview'),
            $this->ctrl->getLinkTarget($this, 'overview')
        );
        $this->ctrl->saveParameter($this, "tplid");

        if (!$this->setting->isAutoGenerated()) {
            $this->tabs->addTab('edit', $this->lng->txt('settings'), $this->ctrl->getLinkTarget($this, 'editTemplate'));
            $this->tabs->addTab(
                'import',
                $this->lng->txt('import'),
                $this->ctrl->getLinkTarget($this, 'showEditImportForm')
            );

            if (in_array($a_tab_active, ['edit', 'settings_trans'])) {
                $this->tabs->addSubTab(
                    'edit',
                    $this->lng->txt('settings'),
                    $this->ctrl->getLinkTarget($this, 'editTemplate')
                );
                $this->tabs->addSubTab(
                    'settings_trans',
                    $this->lng->txt("obj_multilinguality"),
                    $this->ctrl->getLinkTargetByClass(["ilmultilingualismgui"], 'listTranslations')
                );
                $this->tabs->setTabActive('edit');
                $this->tabs->setSubTabActive($a_tab_active);
            } else {
                $this->tabs->setTabActive($a_tab_active);
            }
        }
    }

    public function showEditImportForm(ilPropertyFormGUI $form = null): void
    {
        $this->initTemplateFromRequest();
        $this->setEditTabs("import");
        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->editImportForm();
        }
        $this->tpl->setContent($form->getHTML());
    }

    public function editImportForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setShowTopButtons(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('didactic_import_table_title'));
        $form->addCommandButton('importTemplate', $this->lng->txt('import'));
        $form->addCommandButton('overview', $this->lng->txt('cancel'));

        $file = new ilFileInputGUI($this->lng->txt('didactic_template_update_import'), 'file');
        $file->setRequired(true);
        $file->setSuffixes(['xml']);
        $file->setInfo($this->lng->txt('didactic_template_update_import_info'));
        $form->addItem($file);

        return $form;
    }

    public function editImport(ilDidacticTemplateSetting $a_settings): void
    {
        ilDidacticTemplateObjSettings::transferAutoGenerateStatus($a_settings->getId(), $a_settings->getId());
        $assignments = ilDidacticTemplateObjSettings::getAssignmentsByTemplateID($a_settings->getId());
        $a_settings->delete();
        foreach ($assignments as $obj) {
            ilDidacticTemplateObjSettings::assignTemplate($obj["ref_id"], $obj["obj_id"], $a_settings->getId());
        }
        $this->ctrl->setParameter($this, "tplid", $a_settings->getId());
    }
}
