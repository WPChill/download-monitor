<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit80ce4473100edd20fd6c17775a76ce9a
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Never5\\DownloadMonitor\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Never5\\DownloadMonitor\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'DLM_Admin' => __DIR__ . '/../..' . '/src/Admin/Admin.php',
        'DLM_Admin_Dashboard' => __DIR__ . '/../..' . '/src/Admin/Dashboard.php',
        'DLM_Admin_Extensions' => __DIR__ . '/../..' . '/src/Admin/Extensions.php',
        'DLM_Admin_Fields_Field' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Field.php',
        'DLM_Admin_Fields_Field_ActionButton' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/ActionButton.php',
        'DLM_Admin_Fields_Field_Checkbox' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Checkbox.php',
        'DLM_Admin_Fields_Field_Radio' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Radio.php',
        'DLM_Admin_Fields_Field_Desc' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Desc.php',
        'DLM_Admin_Fields_Field_Factory' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/FieldFactory.php',
        'DLM_Admin_Fields_Field_HtaccessStatus' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/HtaccessStatus.php',
        'DLM_Admin_Fields_Field_Lazy_Select' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/LazySelect.php',
        'DLM_Admin_Fields_Field_Password' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Password.php',
        'DLM_Admin_Fields_Field_Select' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Select.php',
        'DLM_Admin_Fields_Field_Text' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Text.php',
        'DLM_Admin_Fields_Field_Textarea' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Textarea.php',
        'DLM_Admin_Fields_Field_Editor' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Editor.php',
        'DLM_Admin_Fields_Field_Title' => __DIR__ . '/../..' . '/src/Admin/Settings/Fields/Title.php',
        'DLM_Admin_Helper' => __DIR__ . '/../..' . '/src/Admin/class-dlm-admin-helper.php',
        'DLM_Admin_Media_Browser' => __DIR__ . '/../..' . '/src/Admin/MediaBrowser.php',
        'DLM_Admin_Media_Insert' => __DIR__ . '/../..' . '/src/Admin/MediaInsert.php',
        'DLM_Admin_OptionsUpsells' => __DIR__ . '/../..' . '/src/Admin/OptionsUpsells.php',
        'DLM_Admin_Scripts' => __DIR__ . '/../..' . '/src/Admin/AdminScripts.php',
        'DLM_Admin_Settings' => __DIR__ . '/../..' . '/src/Admin/Settings/Settings.php',
        'DLM_Admin_Writepanels' => __DIR__ . '/../..' . '/src/Admin/WritePanels.php',
        'DLM_Ajax' => __DIR__ . '/../..' . '/src/Ajax/Ajax.php',
        'DLM_Ajax_CreatePage' => __DIR__ . '/../..' . '/src/Ajax/CreatePage.php',
        'DLM_Ajax_GetDownloads' => __DIR__ . '/../..' . '/src/Ajax/GetDownloads.php',
        'DLM_Ajax_GetVersions' => __DIR__ . '/../..' . '/src/Ajax/GetVersions.php',
        'DLM_Ajax_Handler' => __DIR__ . '/../..' . '/src/AjaxHandler.php',
        'DLM_Ajax_Manager' => __DIR__ . '/../..' . '/src/Ajax/Manager.php',
        'DLM_Category_Walker' => __DIR__ . '/../..' . '/src/Admin/CategoryWalker.php',
        'DLM_Constants' => __DIR__ . '/../..' . '/src/Constants.php',
        'DLM_Cookie_Manager' => __DIR__ . '/../..' . '/src/CookieManager.php',
        'DLM_Custom_Actions' => __DIR__ . '/../..' . '/src/Admin/CustomActions.php',
        'DLM_Custom_Columns' => __DIR__ . '/../..' . '/src/Admin/CustomColumns.php',
        'DLM_Custom_Labels' => __DIR__ . '/../..' . '/src/Admin/CustomLabels.php',
        'DLM_Debug_Logger' => __DIR__ . '/../..' . '/src/DebugLogger.php',
        'DLM_Download' => __DIR__ . '/../..' . '/src/Download/Download.php',
        'DLM_DownloadPreview_Config' => __DIR__ . '/../..' . '/src/DownloadPreview/Config.php',
        'DLM_DownloadPreview_Preview' => __DIR__ . '/../..' . '/src/DownloadPreview/Preview.php',
        'DLM_Download_Duplicator_AAM' => __DIR__ . '/../..' . '/src/Admin/Duplicate/DownloadDuplicatorAAM.php',
        'DLM_Download_Factory' => __DIR__ . '/../..' . '/src/Download/DownloadFactory.php',
        'DLM_Download_Handler' => __DIR__ . '/../..' . '/src/DownloadHandler.php',
        'DLM_Download_No_Access_Page_Endpoint' => __DIR__ . '/../..' . '/src/DownloadNoAccessPageEndpoint.php',
        'DLM_Download_Repository' => __DIR__ . '/../..' . '/src/Download/DownloadRepository.php',
        'DLM_Download_Version' => __DIR__ . '/../..' . '/src/Version/Version.php',
        'DLM_File_Manager' => __DIR__ . '/../..' . '/src/FileManager.php',
        'DLM_Gutenberg' => __DIR__ . '/../..' . '/src/Gutenberg.php',
        'DLM_Hasher' => __DIR__ . '/../..' . '/src/Hasher.php',
        'DLM_Installer' => __DIR__ . '/../..' . '/src/Installer.php',
        'DLM_Integrations_PostTypesOrder' => __DIR__ . '/../..' . '/src/Integrations/PostTypesOrder.php',
        'DLM_Integrations_YoastSEO' => __DIR__ . '/../..' . '/src/Integrations/YoastSEO.php',
        'DLM_LU_Ajax' => __DIR__ . '/../..' . '/src/LegacyUpgrader/Ajax.php',
        'DLM_LU_Checker' => __DIR__ . '/../..' . '/src/LegacyUpgrader/Checker.php',
        'DLM_LU_Content_Queue' => __DIR__ . '/../..' . '/src/LegacyUpgrader/ContentQueue.php',
        'DLM_LU_Content_Upgrader' => __DIR__ . '/../..' . '/src/LegacyUpgrader/ContentUpgrader.php',
        'DLM_LU_Download_Queue' => __DIR__ . '/../..' . '/src/LegacyUpgrader/DownloadQueue.php',
        'DLM_LU_Download_Upgrader' => __DIR__ . '/../..' . '/src/LegacyUpgrader/DownloadUpgrader.php',
        'DLM_LU_Message' => __DIR__ . '/../..' . '/src/LegacyUpgrader/Message.php',
        'DLM_LU_Page' => __DIR__ . '/../..' . '/src/LegacyUpgrader/Page.php',
        'DLM_Log_Export_CSV' => __DIR__ . '/../..' . '/src/Logs/LogExportCSV.php',
        'DLM_Log_Filters' => __DIR__ . '/../..' . '/src/Logs/LogFilters.php',
        'DLM_Log_Item' => __DIR__ . '/../..' . '/src/Logs/LogItem.php',
        'DLM_Log_Item_Repository' => __DIR__ . '/../..' . '/src/Logs/LogItemRepository.php',
        'DLM_Log_Page' => __DIR__ . '/../..' . '/src/Logs/LogPage.php',
        'DLM_Logging' => __DIR__ . '/../..' . '/src/Logs/Logging.php',
        'DLM_Logging_List_Table' => __DIR__ . '/../..' . '/src/Logs/LoggingListTable.php',
        'DLM_Post_Type_Manager' => __DIR__ . '/../..' . '/src/PostTypeManager.php',
        'DLM_Product' => __DIR__ . '/../..' . '/src/Product/Product.php',
        'DLM_Product_Error_Handler' => __DIR__ . '/../..' . '/src/Product/ProductErrorHandler.php',
        'DLM_Product_License' => __DIR__ . '/../..' . '/src/Product/ProductLicense.php',
        'DLM_Product_Manager' => __DIR__ . '/../..' . '/src/Product/ProductManager.php',
        'DLM_Reports_Ajax' => __DIR__ . '/../..' . '/src/Admin/Reports/Ajax.php',
        'DLM_Reports_Chart' => __DIR__ . '/../..' . '/src/Admin/Reports/Chart.php',
        'DLM_Reports_Page' => __DIR__ . '/../..' . '/src/Admin/Reports/Page.php',
        'DLM_Review' => __DIR__ . '/../..' . '/includes/admin/class-dlm-review.php',
        'DLM_Search' => __DIR__ . '/../..' . '/src/Search.php',
        'DLM_Services' => __DIR__ . '/../..' . '/src/Services.php',
        'DLM_Settings_Helper' => __DIR__ . '/../..' . '/src/Admin/Settings/SettingsHelper.php',
        'DLM_Settings_Page' => __DIR__ . '/../..' . '/src/Admin/Settings/Page.php',
        'DLM_Shortcodes' => __DIR__ . '/../..' . '/src/Shortcodes.php',
        'DLM_Taxonomy_Manager' => __DIR__ . '/../..' . '/src/TaxonomyManager.php',
        'DLM_Template_Handler' => __DIR__ . '/../..' . '/src/TemplateHandler.php',
        'DLM_Transient_Manager' => __DIR__ . '/../..' . '/src/TransientManager.php',
        'DLM_Uninstall' => __DIR__ . '/../..' . '/includes/admin/uninstall/class-dlm-uninstall.php',
        'DLM_Upgrade_Manager' => __DIR__ . '/../..' . '/src/UpgradeManager.php',
        'DLM_Upsells' => __DIR__ . '/../..' . '/includes/admin/class-dlm-upsells.php',
        'DLM_Utils' => __DIR__ . '/../..' . '/src/Utils.php',
        'DLM_Version_Manager' => __DIR__ . '/../..' . '/src/Version/VersionManager.php',
        'DLM_Version_Repository' => __DIR__ . '/../..' . '/src/Version/VersionRepository.php',
        'DLM_View_Manager' => __DIR__ . '/../..' . '/src/Admin/ViewManager.php',
        'DLM_Welcome_Page' => __DIR__ . '/../..' . '/includes/admin/class-dlm-welcome.php',
        'DLM_Widget_Downloads' => __DIR__ . '/../..' . '/src/Widgets/Downloads.php',
        'DLM_Widget_Manager' => __DIR__ . '/../..' . '/src/Widgets/Manager.php',
        'DLM_WordPress_Download_Repository' => __DIR__ . '/../..' . '/src/Download/WordPressDownloadRepository.php',
        'DLM_WordPress_Log_Item_Repository' => __DIR__ . '/../..' . '/src/Logs/WordPressLogItemRepository.php',
        'DLM_WordPress_Version_Repository' => __DIR__ . '/../..' . '/src/Version/WordPressVersionRepository.php',
        'DateTimeImmutable' => __DIR__ . '/../..' . '/src/Polyfill/DateTimeImmutable/DateTimeImmutable.php',
        'DateTimeInterface' => __DIR__ . '/../..' . '/src/Polyfill/DateTimeImmutable/DateTimeInterface.php',
        'Download_Monitor_Usage_Tracker' => __DIR__ . '/../..' . '/includes/tracking/class-download-monitor-usage-tracker.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Curl' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Curl.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Encoder' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Encoder.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Environment' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Environment.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\HttpClient' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/HttpClient.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\HttpException' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/HttpException.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\HttpRequest' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/HttpRequest.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\HttpResponse' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/HttpResponse.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\IOException' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/IOException.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Injector' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Injector.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Serializer' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Serializer.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Serializer\\Form' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Serializer/Form.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Serializer\\FormPart' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Serializer/FormPart.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Serializer\\Json' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Serializer/Json.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Serializer\\Multipart' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Serializer/Multipart.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPalHttp\\Serializer\\Text' => __DIR__ . '/../..' . '/src/Dependencies/PayPalHttp/Serializer/Text.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPal\\Api\\CarrierAccount' => __DIR__ . '/../..' . '/src/Dependencies/PayPal/Api/CarrierAccount.php',
        'Never5\\DownloadMonitor\\Dependencies\\PayPal\\Api\\OpenIdUserinfo' => __DIR__ . '/../..' . '/src/Dependencies/PayPal/Api/OpenIdUserinfo.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Container' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Container.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Exception\\ExpectedInvokableException' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Exception/ExpectedInvokableException.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Exception\\FrozenServiceException' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Exception/FrozenServiceException.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Exception\\InvalidServiceIdentifierException' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Exception/InvalidServiceIdentifierException.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Exception\\UnknownIdentifierException' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Exception/UnknownIdentifierException.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Psr11\\Container' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Psr11/Container.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Psr11\\ServiceLocator' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Psr11/ServiceLocator.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\ServiceIterator' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/ServiceIterator.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\ServiceProviderInterface' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/ServiceProviderInterface.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\Fixtures\\Invokable' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/Fixtures/Invokable.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\Fixtures\\NonInvokable' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/Fixtures/NonInvokable.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\Fixtures\\PimpleServiceProvider' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/Fixtures/PimpleServiceProvider.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\Fixtures\\Service' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/Fixtures/Service.php',
        'Never5\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\ServiceIteratorTest' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/ServiceIteratorTest.php',
        'Never5\\DownloadMonitor\\Dependencies\\Psr\\Container\\ContainerExceptionInterface' => __DIR__ . '/../..' . '/src/Dependencies/Psr/Container/ContainerExceptionInterface.php',
        'Never5\\DownloadMonitor\\Dependencies\\Psr\\Container\\ContainerInterface' => __DIR__ . '/../..' . '/src/Dependencies/Psr/Container/ContainerInterface.php',
        'Never5\\DownloadMonitor\\Dependencies\\Psr\\Container\\NotFoundExceptionInterface' => __DIR__ . '/../..' . '/src/Dependencies/Psr/Container/NotFoundExceptionInterface.php',
        'Never5\\DownloadMonitor\\Shop\\Access\\Manager' => __DIR__ . '/../..' . '/src/Shop/Access/Manager.php',
        'Never5\\DownloadMonitor\\Shop\\Admin\\DownloadOption' => __DIR__ . '/../..' . '/src/Shop/Admin/DownloadOption.php',
        'Never5\\DownloadMonitor\\Shop\\Admin\\Fields\\GatewayOverview' => __DIR__ . '/../..' . '/src/Shop/Admin/Fields/GatewayOverview.php',
        'Never5\\DownloadMonitor\\Shop\\Admin\\OrderTable' => __DIR__ . '/../..' . '/src/Shop/Admin/OrderTable.php',
        'Never5\\DownloadMonitor\\Shop\\Admin\\Pages\\Orders' => __DIR__ . '/../..' . '/src/Shop/Admin/Pages/Orders.php',
        'Never5\\DownloadMonitor\\Shop\\Admin\\ProductTableColumns' => __DIR__ . '/../..' . '/src/Shop/Admin/ProductTableColumns.php',
        'Never5\\DownloadMonitor\\Shop\\Admin\\ShopAdminHelper' => __DIR__ . '/../..' . '/src/Shop/Admin/ShopAdminHelper.php',
        'Never5\\DownloadMonitor\\Shop\\Admin\\WritePanels' => __DIR__ . '/../..' . '/src/Shop/Admin/WritePanels.php',
        'Never5\\DownloadMonitor\\Shop\\Ajax\\AdminChangeOrderStatus' => __DIR__ . '/../..' . '/src/Shop/Ajax/AdminChangeOrderStatus.php',
        'Never5\\DownloadMonitor\\Shop\\Ajax\\Ajax' => __DIR__ . '/../..' . '/src/Shop/Ajax/Ajax.php',
        'Never5\\DownloadMonitor\\Shop\\Ajax\\Manager' => __DIR__ . '/../..' . '/src/Shop/Ajax/Manager.php',
        'Never5\\DownloadMonitor\\Shop\\Ajax\\PlaceOrder' => __DIR__ . '/../..' . '/src/Shop/Ajax/PlaceOrder.php',
        'Never5\\DownloadMonitor\\Shop\\Cart\\Cart' => __DIR__ . '/../..' . '/src/Shop/Cart/Cart.php',
        'Never5\\DownloadMonitor\\Shop\\Cart\\Coupon' => __DIR__ . '/../..' . '/src/Shop/Cart/Coupon.php',
        'Never5\\DownloadMonitor\\Shop\\Cart\\Hooks' => __DIR__ . '/../..' . '/src/Shop/Cart/Hooks.php',
        'Never5\\DownloadMonitor\\Shop\\Cart\\Item\\Factory' => __DIR__ . '/../..' . '/src/Shop/Cart/Item/Factory.php',
        'Never5\\DownloadMonitor\\Shop\\Cart\\Item\\Item' => __DIR__ . '/../..' . '/src/Shop/Cart/Item/Item.php',
        'Never5\\DownloadMonitor\\Shop\\Cart\\Manager' => __DIR__ . '/../..' . '/src/Shop/Cart/Manager.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\Field' => __DIR__ . '/../..' . '/src/Shop/Checkout/Field.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\Manager' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/Manager.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\Address' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/Address.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\Amount' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/Amount.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\BaseAddress' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/BaseAddress.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\CartBase' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/CartBase.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\Details' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/Details.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\FormatConverter' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/FormatConverter.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\Item' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/Item.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\ItemList' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/ItemList.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\NumericValidator' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/NumericValidator.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\Payer' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/Payer.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\PayerInfo' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/PayerInfo.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\RedirectUrls' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/RedirectUrls.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\Transaction' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/Transaction.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\TransactionBase' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/TransactionBase.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Api\\UrlValidator' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Api/UrlValidator.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\CaptureOrder' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/CaptureOrder.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\AccessToken' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/AccessToken.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\AccessTokenRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/AccessTokenRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\AuthorizationInjector' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/AuthorizationInjector.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\FPTIInstrumentationInjector' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/FPTIInstrumentationInjector.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\GzipInjector' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/GzipInjector.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\PayPalEnvironment' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/PayPalEnvironment.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\PayPalHttpClient' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/PayPalHttpClient.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\ProductionEnvironment' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/ProductionEnvironment.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\RefreshTokenRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/RefreshTokenRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\SandboxEnvironment' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/SandboxEnvironment.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\UserAgent' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/UserAgent.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Core\\Version' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Core/Version.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\CreateOrder' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/CreateOrder.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\ExecutePaymentListener' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/ExecutePaymentListener.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Orders\\OrdersAuthorizeRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Orders/OrdersAuthorizeRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Orders\\OrdersCaptureRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Orders/OrdersCaptureRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Orders\\OrdersCreateRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Orders/OrdersCreateRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Orders\\OrdersGetRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Orders/OrdersGetRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Orders\\OrdersPatchRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Orders/OrdersPatchRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Orders\\OrdersValidateRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Orders/OrdersValidateRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\PayPalGateway' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/PayPalGateway.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Payments\\AuthorizationsCaptureRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Payments/AuthorizationsCaptureRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Payments\\AuthorizationsGetRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Payments/AuthorizationsGetRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Payments\\AuthorizationsReauthorizeRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Payments/AuthorizationsReauthorizeRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Payments\\AuthorizationsVoidRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Payments/AuthorizationsVoidRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Payments\\CapturesGetRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Payments/CapturesGetRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Payments\\CapturesRefundRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Payments/CapturesRefundRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PayPal\\Payments\\RefundsGetRequest' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PayPal/Payments/RefundsGetRequest.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\PaymentGateway' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/PaymentGateway.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\Result' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/Result.php',
        'Never5\\DownloadMonitor\\Shop\\Checkout\\PaymentGateway\\Test\\TestGateway' => __DIR__ . '/../..' . '/src/Shop/Checkout/PaymentGateway/Test/TestGateway.php',
        'Never5\\DownloadMonitor\\Shop\\Email\\Handler' => __DIR__ . '/../..' . '/src/Shop/Email/Handler.php',
        'Never5\\DownloadMonitor\\Shop\\Email\\Message' => __DIR__ . '/../..' . '/src/Shop/Email/Message.php',
        'Never5\\DownloadMonitor\\Shop\\Email\\VarParser' => __DIR__ . '/../..' . '/src/Shop/Email/VarParser.php',
        'Never5\\DownloadMonitor\\Shop\\Helper\\Country' => __DIR__ . '/../..' . '/src/Shop/Helper/Country.php',
        'Never5\\DownloadMonitor\\Shop\\Helper\\Currency' => __DIR__ . '/../..' . '/src/Shop/Helper/Currency.php',
        'Never5\\DownloadMonitor\\Shop\\Helper\\Format' => __DIR__ . '/../..' . '/src/Shop/Helper/Format.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Factory' => __DIR__ . '/../..' . '/src/Shop/Order/Factory.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Manager' => __DIR__ . '/../..' . '/src/Shop/Order/Manager.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Order' => __DIR__ . '/../..' . '/src/Shop/Order/Order.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\OrderCoupon' => __DIR__ . '/../..' . '/src/Shop/Order/OrderCoupon.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\OrderCustomer' => __DIR__ . '/../..' . '/src/Shop/Order/OrderCustomer.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\OrderItem' => __DIR__ . '/../..' . '/src/Shop/Order/OrderItem.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Repository' => __DIR__ . '/../..' . '/src/Shop/Order/Repository.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Status\\Factory' => __DIR__ . '/../..' . '/src/Shop/Order/Status/Factory.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Status\\Manager' => __DIR__ . '/../..' . '/src/Shop/Order/Status/Manager.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Status\\OrderStatus' => __DIR__ . '/../..' . '/src/Shop/Order/Status/OrderStatus.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Transaction\\Factory' => __DIR__ . '/../..' . '/src/Shop/Order/Transaction/Factory.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Transaction\\OrderTransaction' => __DIR__ . '/../..' . '/src/Shop/Order/Transaction/OrderTransaction.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\Transaction\\OrderTransactionStatus' => __DIR__ . '/../..' . '/src/Shop/Order/Transaction/OrderTransactionStatus.php',
        'Never5\\DownloadMonitor\\Shop\\Order\\WordPressRepository' => __DIR__ . '/../..' . '/src/Shop/Order/WordPressRepository.php',
        'Never5\\DownloadMonitor\\Shop\\Product\\Factory' => __DIR__ . '/../..' . '/src/Shop/Product/Factory.php',
        'Never5\\DownloadMonitor\\Shop\\Product\\Product' => __DIR__ . '/../..' . '/src/Shop/Product/Product.php',
        'Never5\\DownloadMonitor\\Shop\\Product\\Repository' => __DIR__ . '/../..' . '/src/Shop/Product/Repository.php',
        'Never5\\DownloadMonitor\\Shop\\Product\\WordPressRepository' => __DIR__ . '/../..' . '/src/Shop/Product/WordPressRepository.php',
        'Never5\\DownloadMonitor\\Shop\\Services\\ServiceProvider' => __DIR__ . '/../..' . '/src/Shop/Services/ServiceProvider.php',
        'Never5\\DownloadMonitor\\Shop\\Services\\Services' => __DIR__ . '/../..' . '/src/Shop/Services/Services.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\Cookie' => __DIR__ . '/../..' . '/src/Shop/Session/Cookie.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\Factory' => __DIR__ . '/../..' . '/src/Shop/Session/Factory.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\Item\\Factory' => __DIR__ . '/../..' . '/src/Shop/Session/Item/Factory.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\Item\\Item' => __DIR__ . '/../..' . '/src/Shop/Session/Item/Item.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\Manager' => __DIR__ . '/../..' . '/src/Shop/Session/Manager.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\Repository' => __DIR__ . '/../..' . '/src/Shop/Session/Repository.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\Session' => __DIR__ . '/../..' . '/src/Shop/Session/Session.php',
        'Never5\\DownloadMonitor\\Shop\\Session\\WordPressRepository' => __DIR__ . '/../..' . '/src/Shop/Session/WordPressRepository.php',
        'Never5\\DownloadMonitor\\Shop\\Shortcode\\Buy' => __DIR__ . '/../..' . '/src/Shop/Shortcode/Buy.php',
        'Never5\\DownloadMonitor\\Shop\\Shortcode\\Cart' => __DIR__ . '/../..' . '/src/Shop/Shortcode/Cart.php',
        'Never5\\DownloadMonitor\\Shop\\Shortcode\\Checkout' => __DIR__ . '/../..' . '/src/Shop/Shortcode/Checkout.php',
        'Never5\\DownloadMonitor\\Shop\\Tax\\TaxClassManager' => __DIR__ . '/../..' . '/src/Shop/Tax/TaxClassManager.php',
        'Never5\\DownloadMonitor\\Shop\\Tax\\TaxRate' => __DIR__ . '/../..' . '/src/Shop/Tax/TaxRate.php',
        'Never5\\DownloadMonitor\\Shop\\Util\\Assets' => __DIR__ . '/../..' . '/src/Shop/Util/Assets.php',
        'Never5\\DownloadMonitor\\Shop\\Util\\Page' => __DIR__ . '/../..' . '/src/Shop/Util/Page.php',
        'Never5\\DownloadMonitor\\Shop\\Util\\PostType' => __DIR__ . '/../..' . '/src/Shop/Util/PostType.php',
        'Never5\\DownloadMonitor\\Shop\\Util\\Redirect' => __DIR__ . '/../..' . '/src/Shop/Util/Redirect.php',
        'Never5\\DownloadMonitor\\Shop\\Util\\TemplateInjector' => __DIR__ . '/../..' . '/src/Shop/Util/TemplateInjector.php',
        'Never5\\DownloadMonitor\\Util\\ExtensionLoader' => __DIR__ . '/../..' . '/src/Util/ExtensionLoader.php',
        'Never5\\DownloadMonitor\\Util\\Onboarding' => __DIR__ . '/../..' . '/src/Util/Onboarding.php',
        'Never5\\DownloadMonitor\\Util\\PageCreator' => __DIR__ . '/../..' . '/src/Util/PageCreator.php',
        'WPChill\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\PimpleServiceProviderInterfaceTest' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/PimpleServiceProviderInterfaceTest.php',
        'WPChill\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\PimpleTest' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/PimpleTest.php',
        'WPChill\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\Psr11\\ContainerTest' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/Psr11/ContainerTest.php',
        'WPChill\\DownloadMonitor\\Dependencies\\Pimple\\Tests\\Psr11\\ServiceLocatorTest' => __DIR__ . '/../..' . '/src/Dependencies/Pimple/Tests/Psr11/ServiceLocatorTest.php',
        'WPChill_Welcome' => __DIR__ . '/../..' . '/includes/submodules/banner/class-wpchill-welcome.php',
        'WP_DLM' => __DIR__ . '/../..' . '/src/DLM.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit80ce4473100edd20fd6c17775a76ce9a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit80ce4473100edd20fd6c17775a76ce9a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit80ce4473100edd20fd6c17775a76ce9a::$classMap;

        }, null, ClassLoader::class);
    }
}
