<?php
/**
 * MembersProfilePage_Controller.php
 *
 * @link      http://github.com/zucchi/Silverstripe-Members for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
/**
 * Class MembersProfilePage_Controller
 *
 * Provides controller for displaying MembersProfilePage
 */
class MembersAccountPage_Controller extends Page_Controller
{

    private static $allowed_actions = array (
        'index', 'update'
    );

    protected $member;

    public function init()
    {
        parent::init();
        $this->member = Member::currentUser();
    }

    public function index()
    {
        if (!$this->member) {
            return Security::permissionFailure($this);
        }

        $form = $this->update();

        if ($this->member) {
            $form->loadDataFrom($this->member);
        }

        return array(
            'Member' => $this->member,
            'Form' => $form,
        );
    }

    public function update()
    {
        if (!$this->member) {
            return Security::permissionFailure($this);
        }

        $form = new Form(
            $this,
            'update',
            $this->getAccountFields(),
            new FieldList(
                FormAction::create('save')->setTitle('Save')
            )
        );

        return $form;
    }

    /**
     * Updates an existing Member's profile.
     */
    public function save(array $data, Form $form) {

        $form->saveInto($this->member);

        try {
            $this->member->write();
        } catch(ValidationException $e) {
            $form->sessionMessage($e->getResult()->message(), 'bad');
            return $this->redirectBack();
        }

        $form->sessionMessage (
            _t('MemberProfiles.PROFILEUPDATED', 'Your profile has been updated.'),
            'good'
        );
        return $this->redirectBack();
    }

    /**
     * @param  string $context
     * @return FieldSet
     */
    protected function getAccountFields($member = false) {
        $accountFields = $this->Fields();
        $fields        = new FieldList();

        // depending on the context, load fields from the current member
        if($member) {
            $memberFields = $member->getMemberFormFields();
        } else {
            $memberFields = singleton('Member')->getMemberFormFields();
        }

        foreach($accountFields as $accountField) {
            $visible  = ($accountField->Visibility !== 'Hidden');
            $editable = $accountField->Editable;
            $name        = $accountField->MemberField;
            $memberField = $memberFields->dataFieldByName($name);

            // handle the special case of the Groups control so that only allowed groups can be selected
//            if ($name == 'Groups') {
//                $availableGroups = $this->data()->SelectableGroups();
//                $memberField->setSource($availableGroups);
//            }

            if(!$memberField || !$visible) continue;

            $field = clone $memberField;

            if ($field instanceof UploadField) {
                $field->setCanAttachExisting(false);
                $field->setOverwriteWarning(false);

            }

            if(!$editable) {
                $field->performReadonlyTransformation();
            }

            $field->setTitle($accountField->Title);
            $field->setDescription($accountField->Note);

            if(!$member && $accountField->DefaultValue) {
                $field->setValue($accountField->DefaultValue);
            }

            if($accountField->CustomError) {
                $field->setCustomValidationMessage($accountField->CustomError);
            }

            $fields->push($field);
        }

        $this->extend('updateProfileFields', $fields);
        return $fields;
    }

} 