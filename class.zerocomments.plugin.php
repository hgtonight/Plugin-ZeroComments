<?php if(!defined('APPLICATION')) exit();
/* 	Copyright 2014 Zachary Doll
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation, either version 3 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$PluginInfo['ZeroComments'] = array(
    'Name' => 'Zero Comments',
    'Description' => "A Vanilla Forums plugin in that adds a 'Zero Comments' link to the discussion filter box. Sponsored by VanillaSkins.com - #1 Themeshop for Vanilla.",
    'Version' => '0.2',
    'RequiredApplications' => array('Vanilla' => '2.0.18.13'),
    'RequiredTheme' => FALSE,
    'RequiredPlugins' => FALSE,
    'MobileFriendly' => TRUE,
    'HasLocale' => TRUE,
    'RegisterPermissions' => FALSE,
    'Author' => 'Zachary Doll',
    'AuthorEmail' => 'hgtonight@daklutz.com',
    'AuthorUrl' => 'http://www.daklutz.com',
    'License' => 'GPLv3'
);

class ZeroComments extends Gdn_Plugin {

  /**
   * Whether or not we are in the Zero Comments view
   * @var type
   */
  private $CustomView = FALSE;
  
  /**
   * How many comments are considered zero
   * @var type 
   */
  private $ZERO_COMMENT_COUNT = 0;
  
  public function __construct() {
    if(version_compare(APPLICATION_VERSION, '2.1', '<')) {
      $this->ZERO_COMMENT_COUNT = 1;
    }
    parent::__construct();
  }
  /**
   * The custom view of the discussion list
   * @param type $Sender
   * @param type $Args
   */
  public function DiscussionsController_ZeroComments_Create($Sender, $Args = array()) {
    $this->CustomView = TRUE;
    $Sender->View = 'Index';
    $Sender->SetData('_PagerUrl', 'discussions/zerocomments/{Page}');
    $Sender->Index(GetValue(0, $Args, 'p1'));
  }

  /**
   * Set the count to the cache value. This will use a more pager unless caching
   * is enabled.
   * 
   * @param DiscussionsController $Sender
   */
  public function DiscussionsController_Render_Before($Sender) {
    if($this->CustomView) {
      $Sender->SetData('CountDiscussions', Gdn::Cache()->Get('ZeroComments-Count'));
    }
  }

  /**
   * Returns the count of discussions with zero comments. This is used for an
   * AJAX popin on the discussion filter.
   * @param type $Sender
   */
  public function DiscussionsController_ZeroCommentsCount_Create($Sender) {
    $Count = Gdn::SQL()->GetCount('Discussion', array('CountComments' => $this->ZERO_COMMENT_COUNT));
    Gdn::Cache()->Store('ZeroComments-Count', $Count, array(Gdn_Cache::FEATURE_EXPIRY => 15 * 60));

    $Sender->SetData('UnrequitedCount', $Count);
    $Sender->SetData('_Value', $Count);
    $Sender->Render('Value', 'Utility', 'Dashboard');
  }

  /**
   * Add a link to the discussion filters for 2.1.x.
   * @param type $Sender
   */
  public function Base_AfterDiscussionFilters_Handler($Sender) {
    $Count = Gdn::Cache()->Get('ZeroComments-Count');
    if($Count === Gdn_Cache::CACHEOP_FAILURE) {
      $Count = ' <span class="Aside"><span class="Popin Count" rel="/discussions/zerocommentscount"></span>';
    }
    else {
      $Count = ' <span class="Aside"><span class="Count">' . $Count . '</span></span>';
    }

    echo '<li class="ZeroComments ' . ($this->CustomView === TRUE ? ' Active' : '') . '">' .
            Anchor(Sprite('SpUnansweredQuestions') . ' ' . T('Zero Comments') . $Count,
              '/discussions/zerocomments', 'ZeroComments') . '</li>';
  }

  /**
   * Add a link to the discussion tabs for 2.0.x
   * @param type $Sender
   */
  public function DiscussionsController_AfterDiscussionTabs_Handler($Sender) {
    $Count = Gdn::Cache()->Get('ZeroComments-Count');
    if($Count === Gdn_Cache::CACHEOP_FAILURE) {
      $Count = ' <span class="Popin Count" rel="/discussions/zerocommentscount">';
    }
    else {
      $Count = ' <span class="Count">' . $Count . '</span>';
    }

    echo '<li class="ZeroComments ' . ($this->CustomView === TRUE ? ' Active' : '') .
            '"><a class="TabLink ZeroComments" href="' . Url('/discussions/zerocomments') .
            '">' . T('Zero Comments') . $Count . '</span></a></li>';
  }

  /**
   * Modify the discussion model if we are in the custom view.
   * @param type $Sender
   */
  public function DiscussionModel_BeforeGet_Handler($Sender) {
    if($this->CustomView === TRUE) {
      $Wheres = & $Sender->EventArguments['Wheres'];
      $Wheres['d.CountComments'] = $this->ZERO_COMMENT_COUNT;
      Gdn::Controller()->Title('Zero Comments');
    }
  }

}
