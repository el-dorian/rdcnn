<?php
/** @noinspection SpellCheckingInspection */
return [
    'error' => 'site/error',
    'login' => 'site/login',
    'personal' => 'site/patient',
    'patient_search' => 'site/patient-search',
    'send-message' => 'management/send-message',
    'enter/<authToken:.{256}>' => 'management/handle-mail',
    'unsubscribe/<token:.+>' => 'user/unsubscribe',
    'iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8' => 'site/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8',
    'test' => 'site/test',
    'execution/add' => 'administrator/add-execution',
    'add-examination/check' => 'administrator/check-examination-id',
    'patient/<accessToken:[-_0-9a-zA-Z]+>' => 'site/patient',
    'enter/<accessToken:.+>' => 'user/enter',
    'availability/check' => 'site/availability-check',
    'patients/check' => 'administrator/patients-check',
    'check/files/<executionNumber:[0-9a-zA-Z]+>' => 'administrator/files-check',
    'conclusion/<href:A?\d+-?\d*\.pdf>' => 'download/conclusion',
    'auto-print/<fileName:A?\d+-?\d*\.pdf>' => 'administrator/auto-print',
    'print-conclusion/<conclusionId:\d+>' => 'administrator/print-conclusion',
    'download-dicom/<dicomId:\d+>' => 'administrator/download-dicom',
    'archive-print/<identifier:\d+>' => 'administrator/archive-print',
    'print-conclusion/<href:A?\d+-?\d*\.pdf>' => 'download/print-conclusion',
    'check' => 'site/check',
    'delete-unhandled-folder' => 'administrator/delete-unhandled-folder',
    'rename-unhandled-folder' => 'administrator/rename-unhandled-folder',
    'print-missed-conclusions-list' => 'administrator/print-missed-conclusions-list',
    'download/temp/<link:.+>' => 'download/download-temp',
    'dl/<link:.+>' => 'download/download-once',
    'drop' => 'download/drop',
    'mail/add/<id:\d+>' => 'management/handle-mail',
    'download/c/<id:\d+>' => 'download/conclusion-download',
    'download/d/<id:\d+>' => 'download/dicom-download',
    'download/a/<id:\d+>' => 'download/archive-conclusion-download',
    'print/c/<id:\d+>' => 'print/conclusion-print',
    'print/a/<id:\d+>' => 'print/archive-conclusion-print',
    'check-share-link/<type:conclusion|execution>/<id:\d+>' => 'share/check-share-link',
    'shared/<link:.{64}>' => 'share/handle-link',
    'patient-mail/add/<examinationId:\d+>' => 'management/get-patient-mail-add-form',
    'patient-mail/change/<examinationId:\d+>' => 'management/get-patient-mail-change-form',
    'mail/add' => 'management/add-mail',
    'mail/change' => 'management/change-mail',
    'mail/delete' => 'management/delete-mail',
    'send-info-mail/<id:\d+>' => 'administrator/send-info-mail',
    'next/<center:nv|aurora|ct>' => 'administrator/register-next-patient',
    'delete/conclusions/<executionNumber:[0-9a-zA-Z]+>' => 'management/delete-conclusions',
    '/dicom-viewer' => 'site/dicom-viewer',
    'api' => 'api/do',
    'get-file' => 'api/file',
    'rated' => 'user/rate-link-clicked',
    'rate' => 'user/rate',
    'show-changes' => 'administrator/show-notifications',
    'delete/conc/<filename:.+>' => 'administrator/delete-conclusion-file',
    'archive-dl/<id:\d+>' => 'download/download-from-archive',
    'santa' => 'secret-santa/registration',
    'review' => 'user/review',
    'santa/send/<key:.+>' => 'secret-santa/sent',
    'santa/not-received/<key:.+>' => 'secret-santa/not-received',
    'schedule/hash' => 'user/show-schedule-hash',
    'schedule/get/<key:\d+>' => 'api/get-schedule',
    'print/<executionNumber:[0-9a-zA-Z]+>' => 'administrator/print-conclusions',
    'enable/<executionNumber:[0-9a-zA-Z]+>' => 'administrator/account-enable',
    'create/<executionNumber:[0-9a-zA-Z]+>' => 'administrator/account-create',
    'enable/<executionNumber:[0-9a-zA-Z]+>/<changePass:\d+>' => 'administrator/account-enable',
    'dicom/how-to' => 'site/dicom-hint'
];