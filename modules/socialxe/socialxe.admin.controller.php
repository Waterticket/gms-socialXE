<?php
	/**
	 * @class  socialxeAdminController
     * @author CONORY (https://xe.conory.com)
	 * @brief The admin controller class of the socialxe module
	 */
	
	class socialxeAdminController extends socialxe
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
        /**
         * @brief API ����
         **/
        function procSocialxeAdminSettingApi()
		{
            $args = Context::getRequestVars();
			
			$config_names = array(
				'twitter_consumer_key',
				'twitter_consumer_secret',
				'facebook_app_id',
				'facebook_app_secret',
				'google_client_id',
				'google_client_secret',
				'naver_client_id',
				'naver_client_secret',
				'kakao_client_id',
			);
			
			$config = $this->config;
			
			foreach($config_names as $val)
			{
				$config->{$val} = $args->{$val};
			}
			
            getController('module')->insertModuleConfig('socialxe', $config);	
			
            $this->setMessage('success_updated');
			
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialxeAdminSettingApi'));
        }
		
        /**
         * @brief ȯ�漳��
         **/
        function procSocialxeAdminSetting()
		{
            $args = Context::getRequestVars();
			
			$config_names = array(
				'delete_auto_log_record',
				'sns_services',
				'sns_profile',
				'layout_srl',
				'skin',
				'mlayout_srl',
				'mskin',
				'sns_login',
				'default_login',
				'default_signup',
				'delete_member_forbid',
				'sns_follower_count',
				'mail_auth_valid_hour',
				'sns_suspended_account',
				'sns_keep_signed',
				'sns_input_add_info',
				'linkage_module_srl',
				'linkage_module_target',
			);
			
			$config = $this->config;
			
			foreach($config_names as $val)
			{
				$config->{$val} = $args->{$val};
			}
			
            getController('module')->insertModuleConfig('socialxe', $config);	
			
            $this->setMessage('success_updated');
			
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialxeAdminSetting'));
        }
		
        /**
         * @brief �αױ�� ����
         **/
        function procSocialxeAdminDeleteLogRecord()
		{
			$args = new stdClass;
			
		    if(Context::get('date_srl'))
			{
				$args->regdate = Context::get('date_srl');
			}
			
            $output = executeQuery('socialxe.deleteLogRecord', $args);	
            if(!$output->toBool())
			{
				return $output;
			}
			
            $this->setMessage('success_deleted');
			
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialxeAdminLogRecord'));
        }
	}