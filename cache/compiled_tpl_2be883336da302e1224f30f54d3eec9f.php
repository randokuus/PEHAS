<?php defined("MODERA_KEY")|| die(); ?><style>
    .form-element-width {
        width: 300px;
    }
</style>
    <script language="JavaScript" type="text/javascript">
    <!--
    function emptyList( box ) {
        while ( box.length ) box.options[0] = null;
    }
    
    function do_get_users_by_groups_cb(users) {
        userList = document.getElementById('client');
        emptyList(userList);
        if (users != false) {
            for (var i in users) {
                option = new Option(users[i].name, users[i].id);
                userList.options[userList.length] = option;
                userList.options[userList.length - 1].selected = true;
            }
        }
    }

    function do_get_users_by_groups(groups, sendType, faculty) {
        x_get_users_by_groups(groups, sendType, faculty, do_get_users_by_groups_cb);
    }

    function refreshClientList() {
        var groups = document.vorm.elements['group'];
        //var sendType = document.vorm.elements['send_type'].value;
        var sendType = '3'; //DEFAULT type is always SMS
        var faculty = document.vorm.elements['faculty'].value;

        var selectedGroups = "";
        for (i = 0; i < groups.length; i++) {
            if (groups[i].selected) {
                if (selectedGroups) {
                    selectedGroups += ",";
                }
                selectedGroups += groups[i].value;
            }
        }
        do_get_users_by_groups(selectedGroups, sendType, faculty);
    }

    function refreshFields() {
        //var sendType = document.vorm.elements['send_type'].value;
        var sendType = '3'; //DEFAULT type is always SMS
        var schoolBlock = document.getElementById('school_sms_credit_block');
        var titleBlock = document.getElementById('title_block');

        switch (sendType) {
            case '3': // SMS
                schoolBlock.style.display = '';
                titleBlock.style.display = 'none';
                break;
            default:
                schoolBlock.style.display = 'none';
                titleBlock.style.display = '';
                break;
        }

    }

    function refreshRecipientFields() {
        var recipientType = document.vorm.elements['recipient_type'].value;
        var recipientBlock1 = document.getElementById('recipient_block1');
        var recipientBlock2 = document.getElementById('recipient_block2');

        switch (recipientType) {
            case '1': // Groups
                recipientBlock1.style.display = '';
                recipientBlock2.style.display = 'none';
                break;
            default:
                recipientBlock1.style.display = 'none';
                recipientBlock2.style.display = '';
                break;
        }
    }

    $(document).ready(function(){
        refreshFields();
        refreshRecipientFields();

        $("#message_content").find("[type='textarea']").bind('input propertychange', function() {
            var characters = $(this).val().length;
            $('#character_count').html(characters);
            $('#message_count').html(Math.ceil(characters/160));
        });
    });

    //-->
    </script>

    <?php if(isset($data["MESSAGE"]) && is_array($data["MESSAGE"])){ foreach($data["MESSAGE"] as $_foreach["MESSAGE"]){ ?>

        <!--msgWrap-->
        <div class="msgWrap">
            <p class="msg msgError msgGray">
                <span><?php echo $this->getTranslate("output|error_occurred"); ?> <?php echo $_foreach["MESSAGE"]["MESSAGE"]; ?></span>
            </p>
        </div>
        <!--/msgWrap-->
    <?php }} ?>


    <?php if(isset($data["IMESSAGE"]) && is_array($data["IMESSAGE"])){ foreach($data["IMESSAGE"] as $_foreach["IMESSAGE"]){ ?>

        <!--msgWrap-->
        <div class="msgWrap">
            <p class="msg msgOk">
                <span><?php echo $_foreach["IMESSAGE"]["IMESSAGE"]; ?></span>
            </p>
        </div>
        <!--/msgWrap-->
    <?php }} ?>


      <!--col1-->
      <div class="col1">
          <!--colInner-->
          <div class="colInner">
              <!--box-->
              <div class="box">
                  <div class="inner">
                      <div class="heading">
                          <h2><?php echo $this->getTranslate("module_messages|add_message"); ?></h2>
                      </div>
                      <form name="vorm" method="post" action="<?php echo $data["SELF"]; ?>" class="">
                          <?php echo $data["HIDDEN"]; ?>

                          <!--formTable-->
                          <div class="formTable ">
                              <!--fRow-->
                              <div class="fRow">
                                  <!--fLine-->
                                  <div class="fLine fRequired" id="school_sms_credit_block" style="display: none;">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|school_sms_credit"); ?>:
                                      </div>
                                      <div class="fCell">
                                          <div class="wCheck">
                                              <?php echo $data["FIELD_SCHOOL_ID"]; ?>
                                          </div>
                                          <a href="mailto:tellimus@koolisysteemid.ee?subject=<?php echo $this->getTranslate("module_messages|sms_credit_order"); ?>"><?php echo $this->getTranslate("module_messages|order_more"); ?></a>
                                      </div>
                                      <div class="fHint">

                                      </div>
                                  </div>
                                  <!--/fLine-->
                                  <!--fLine-->
                                  <div class="fLine fRequired" id="title_block" style="display: none;">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|title"); ?>:
                                      </div>
                                      <div class="fCell">
                                          <?php echo $data["FIELD_TITLE"]; ?>
                                      </div>
                                      <div class="fHint">

                                      </div>
                                  </div>
                                  <!--/fLine-->
                              </div>
                              <!--/fRow-->
                              <!--fRow-->
                              <div class="fRow">
                                  <!--fLine-->
                                  <div class="fLine fRequired">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|content"); ?>:
                                      </div>
                                      <div class="fCell" id="message_content">
                                          <?php echo $data["FIELD_TEXT"]; ?>
                                              <div><h2><?php echo $this->getTranslate("module_messages|characters"); ?>:<span id="character_count">0</span></h2><h2><?php echo $this->getTranslate("module_messages|smses"); ?>:<span id="message_count">0</span></h2></div>
                                      </div>
                                      <div class="fHint">

                                      </div>
                                  </div>
                                  <!--/fLine-->
                              </div>
                              <!--/fRow-->
                              <!--fRow-->
                              <div class="fRow">
                                  <!--fLine-->
                                  <div class="fLine">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|recipient_type"); ?>:
                                      </div>
                                      <div class="fCell">
                                          <?php echo $data["FIELD_RECIPIENT_TYPE"]; ?>
                                      </div>
                                      <div class="fHint">

                                      </div>
                                  </div>
                                  <!--/fLine-->
                              </div>
                              <!--/fRow-->
                              <!--fRow-->
                              <div class="fRow" id="recipient_block1" style="display: none;">
                                  <!--fLine-->
                                  <div class="fLine">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|group"); ?>:
                                      </div>
                                      <div class="fCell">
                                          <?php echo $data["FIELD_GROUP"]; ?>
                                      </div>
                                      <div class="fHint">

                                      </div>
                                  </div>
                                  <!--/fLine-->
                                  <!--fLine-->
                                  <div class="fLine">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|faculty"); ?>:
                                      </div>
                                      <div class="fCell">
                                          <?php echo $data["FIELD_FACULTY"]; ?>
                                      </div>
                                      <div class="fHint">

                                      </div>
                                  </div>
                                  <!--/fLine-->
                                  <!--fLine-->
                                  <div class="fLine fRequired">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|client"); ?>:
                                      </div>
                                      <div class="fCell">
                                          <?php echo $data["FIELD_CLIENT"]; ?>
                                      </div>
                                      <div class="fHint">

                                      </div>
                                  </div>
                                  <!--/fLine-->
                              </div>
                              <!--/fRow-->
                              <!--fRow-->
                              <div class="fRow" style="display: none;">
                              </div>
                              <!--/fRow-->
                              <!--fRow-->
                              <div class="fRow" id="recipient_block2" style="display: none;">
                                  <!--fLine-->
                                  <div class="fLine fRequired">
                                      <div class="fHead">
                                          <?php echo $this->getTranslate("module_messages|person_numbers"); ?>:
                                      </div>
                                      <div class="fCell">
                                          <?php echo $data["FIELD_PERSON_NUMBERS"]; ?>
                                      </div>
                                      <div class="fHint">
                                          <p><span><?php echo $this->getTranslate("module_messages|person_numbers_help"); ?></span></p>
                                      </div>
                                  </div>
                                  <!--/fLine-->
                              </div>
                              <!--/fRow-->
                              <!--fSubmit-->
                              <div class="wButtons">
                                  <div class="jNiceButton"><div><input type="submit" value="<?php echo $data["BUTTON"]; ?>" class="jNiceButtonInput" /></div></div>
                              </div>
                              <!--/fSubmit-->
                          </div>
                          <!--/formTable-->
                      </form>
                  </div>
                  <!--/inner-->
              </div>
              <!--/box-->
          </div>
          <!--/colInner-->
      </div>
      <!--/col1-->
