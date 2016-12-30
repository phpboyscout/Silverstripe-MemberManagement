<?php
/**
 * @package silverstripe-memberprofiles
 */
class MembersAccountField extends DataObject
{

    private static $db = array(
        'Visibility'     => 'Enum("Display,Hidden", "Hidden")',
        'Editable'       => 'Enum("Yes,No", "No")',
        'MemberField'    => 'Varchar(100)',
        'CustomTitle'    => 'Varchar(100)',
        'DefaultValue'   => 'Text',
        'Note'           => 'Varchar(255)',
        'CustomError'    => 'Varchar(255)',
        'Sort'           => 'Int'
    );

    private static $has_one = array(
        'AccountPage' => 'MembersAccountPage'
    );

    private static $summary_fields = array(
        'DefaultTitle'           => 'Field',
        'Visibility'             => 'Visibility',
        'Editable'             => 'Editable',
        'CustomTitle'            => 'Custom Title',
    );

    private static $default_sort = 'Sort';

    /**
     * Temporary local cache of form fields - otherwise we can potentially be calling
     * getMemberFormFields 20 - 30 times per request via getDefaultTitle.
     *
     * It's declared as a static so all instances have access to it after it's
     * loaded the first time.
     *
     * @var FieldSet
     */
    protected static $member_fields;

    /**
     * @return
     */
    public function getCMSFields()
    {
        Requirements::javascript('memberprofiles/javascript/MemberProfileFieldCMS.js');

        $fields = parent::getCMSFields();
        $memberFields = $this->getMemberFields();
        $memberField = $memberFields->dataFieldByName($this->MemberField);

        $fields->removeByName('MemberField');
        $fields->removeByName('ProfilePageID');

        $fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
            'CustomTitle',
            'DefaultValue',
            'Note',
            'Visibility',
            'Editable',
            'Required'
        ));

        $fields->unshift(new ReadonlyField(
            'MemberField', _t('MemberProfiles.MEMBERFIELD', 'Member Field')
        ));


        $fields->insertBefore(
            new HeaderField('ValidationHeader', _t('MemberProfiles.VALIDATION', 'Validation')),
            'CustomError'
        );

        if ($memberField instanceof DropdownField) {
            $fields->replaceField('DefaultValue', $default = new DropdownField(
                'DefaultValue',
                _t('MemberProfiles.DEFAULTVALUE', 'Default Value'),
                $memberField->getSource()
            ));
            $default->setHasEmptyDefault(true);
        } elseif ($memberField instanceof TextField) {
            $fields->replaceField('DefaultValue', new TextField(
                'DefaultValue', _t('MemberProfiles.DEFAULTVALUE', 'Default Value')
            ));
        } else {
            $fields->removeByName('DefaultValue');
        }

        $fields->dataFieldByName('Visibility')->setSource(array(
            'Display'      => _t('MemberProfiles.ALWAYSDISPLAY', 'Always display'),
            'Hidden'       => _t('MemberProfiles.DONTDISPLAY', 'Do not display')
        ));

        $fields->dataFieldByName('Editable')->setTitle(_t(
            'MemberProfiles.DEFAULTPUBLIC', 'Can this field be edited?'
        ));

        $this->extend('updateMemberProfileCMSFields', $fields);

        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->Sort) {
            $this->Sort = MemberProfileField::get()->max('Sort') + 1;
        }
    }


    /**
     * @uses   MemberProfileField::getDefaultTitle
     * @return string
     */
    public function getTitle()
    {
        if ($this->CustomTitle) {
            return $this->CustomTitle;
        } else {
            return $this->getDefaultTitle(false);
        }
    }

    /**
     * Get the default title for this field from the form field.
     *
     * @param  bool $force Force a non-empty title to be returned.
     * @return string
     */
    public function getDefaultTitle($force = true)
    {
        $fields = $this->getMemberFields();
        $field  = $fields->dataFieldByName($this->MemberField);
        $title  = $field->Title();

        if (!$title && $force) {
            $title = $field->getName();
        }

        return $title;
    }

    protected function getMemberFields()
    {
        if (!self::$member_fields) {
            self::$member_fields = singleton('Member')->getMemberFormFields();
        }
        return self::$member_fields;
    }
}
