<?php
/**
 * MembersAccountPage.php
 *
 * @link      http://github.com/zucchi/Silverstripe-Members for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
/**
 * Class MembersAccountPage
 *
 * Provides page object for display and manipulation of member Account Info
 */
class MembersAccountPage extends Page
{
    private static $db = array();

    private static $has_many = array(
        'Fields' => 'MembersAccountField'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeFieldFromTab('Root.Main', 'Content', true);

        $fields->addFieldsToTab('Root.Fields', array(
            new GridField(
                'Fields',
                _t('MemberProfiles.PROFILEFIELDS', 'Account Fields'),
                $this->Fields(),
                $grid = GridFieldConfig_RecordEditor::create()
                    ->removeComponentsByType('GridFieldDeleteAction')
                    ->removeComponentsByType('GridFieldAddNewButton')
            )
        ));

        return $fields;
    }


    public function onAfterWrite()
    {
        $this->addMemberAccountFields($this);
        parent::onAfterWrite();
    }


    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $accountPages = DataObject::get('MembersAccountPage');
        foreach ($accountPages as $page) {
            $this->addMemberAccountFields($page);
        }
    }

    protected function addMemberAccountFields($page)
    {
        // create default field records
        $memberFields = singleton('Member')->getMemberFormFields()->dataFields();

        $pageFieldNames = array();
        foreach ($page->Fields() as $pageField) {
            $pageFieldNames[] = $pageField->MemberField;
        }

        foreach ($memberFields as $memberField) {
            if (!in_array($memberField->name, $pageFieldNames)) {
                $profileField = new MembersAccountField();
                $profileField->MemberField = $memberField->name;
                $profileField->AccountPageID = $page->ID;
                $profileField->write();
            } else {
                // remove field from $pageFieldNmes
                $key = array_search($memberField->name, $pageFieldNames);
                unset($pageFieldNames[$key]);
            }
        }

        // for each remaining item in $pageFieldNames delete the MemberAccountField
        foreach ($pageFieldNames as $name) {
            $toRemove = MembersAccountField::get()->filter('MemberField', $name);
            foreach ($toRemove as $removeField) {
                $removeField->delete();
            }
        }
    }
}
