<?php /** @noinspection PhpUndefinedClassInspection */

namespace app\controllers;

use app\models\AdministratorActions;
use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\LoginForm;
use app\models\User;
use app\models\Utils;
use app\models\utils\Management;
use app\models\utils\PatientSearch;
use app\priv\Info;
use JetBrains\PhpStorm\ArrayShape;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        return parent::beforeAction($action);
    }

    /**
     * access control
     */
    #[ArrayShape(['access' => "array"])] public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    die('denied');
                    return $this->redirect('/site/error', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                            'privacy-policy',
                            'error',
                            'login'
                        ],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['check'],
                        'roles' => ['?', '@'],
                        'ips' => Info::ACCEPTED_IPS,
                    ],
                    [
                        'allow' => true,
                        'actions' => ['personal-control'],
                        'roles' => ['?', '@'],
                        'ips' => Info::ACCEPTED_IPS,
                    ],

                    [
                        'allow' => true,
                        'actions' => [
                            'dicom-hint',
                            'patient',
                            'availability-check'
                        ],
                        'roles' => ['@'],
                    ],

                    [
                        'allow' => true,
                        'actions' => [
                            'management',
                            'patient-search'
                        ],
                        'roles' => [
                            'manager'
                        ],
                        //'ips' => Info::ACCEPTED_IPS,
                    ],
                ],
            ],
        ];
    }

    #[ArrayShape(['error' => "string[]"])] public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }


    /**
     * Displays homepage.
     *
     * @return array|Response|string
     */
    public function actionIndex(): array|Response|string
    {
        // если пользователь не залогинен-показываю ему страницу с предложением ввести номер обследования и пароль
        if (Yii::$app->user->isGuest) {
            return $this->redirect(Url::toRoute("/site/login"), 301);
        }
        // если пользователь залогинен как администратор-показываю ему страницу для скачивания
        if (Yii::$app->user->can('manage')) {
            return $this->redirect(Url::toRoute("/site/personal-control"), 301);
        }
        if (Yii::$app->user->can('read')) {
            return $this->redirect(Url::toRoute("/site/patient"), 301);
        }
        return $this->render('error', ['message' => 'Приветики']);
    }

    /**
     * @return string|Response
     * @throws \Exception
     */
    public function actionPersonalControl(): Response|string
    {
        // если пользователь не залогинен-показываю ему страницу с предложением ввести номер обследования и пароль
        if (Yii::$app->user->isGuest) {
            if (Yii::$app->request->isGet) {
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_ADMIN_LOGIN]);
                return $this->render('administrationLogin', ['model' => $model]);
            }
            if (Yii::$app->request->isPost) {
                // попробую залогинить
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_ADMIN_LOGIN]);
                $model->load(Yii::$app->request->post());
                if ($model->loginAdmin()) {
                    // загружаю страницу управления
                    return $this->redirect('/site/personal-control', 301);
                }
                return $this->render('administrationLogin', ['model' => $model]);
            }
            // зарегистрирую пользователя как администратора
            //LoginForm::autoLoginAdmin();
        }
        // если пользователь админ
        if (Yii::$app->user->can('manage')) {
            // очищу неиспользуемые данные
            //AdministratorActions::clearGarbage();
            $this->layout = 'administrate';
            if (Yii::$app->request->isPost) {
                // выбор центра, обследования которого нужно отображать
                AdministratorActions::selectCenter();
                AdministratorActions::selectTime();
                AdministratorActions::selectSort();
                return $this->redirect('/site/personal-control', 301);
            }
            // получу все зарегистрированные обследования
            $executionsList = User::findAllRegistered();
            // отсортирую список
            $executionsList = Utils::sortExecutions($executionsList);
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            return $this->render('personal-control', ['executions' => $executionsList, 'model' => $model]);
        }

// редирект на главную
        return $this->redirect('/site/index', 301);
    }


    /**
     * @throws NotFoundHttpException
     */
    public function actionError(): string
    {
        throw new NotFoundHttpException();
    }

    public function actionLogout(): Response
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->user->logout();
            return $this->redirect('/', 301);
        }
        return $this->redirect('/', 301);
    }

    /**
     * @return array
     * @throws Throwable
     */
    public function actionAvailabilityCheck(): array
    {
        try {
            Management::handleChanges();
        } catch (\Exception) {

        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::checkAvailability();
    }

    /**
     */
    public function actionCheck(): void
    {
        try {
            Management::handleChanges();
            ExecutionHandler::check();
        } catch (\Exception) {
        }
    }

    public function actionManagement(): string
    {
        $outputInfo = FileUtils::getOutputInfo();
        $errorsInfo = FileUtils::getErrorInfo();
        $updateOutputInfo = FileUtils::getUpdateOutputInfo();
        $updateErrorsInfo = FileUtils::getUpdateErrorInfo();
        $errors = FileUtils::getServiceErrorsInfo();
        return $this->render('management', ['outputInfo' => $outputInfo, 'errorsInfo' => $errorsInfo, 'errors' => $errors, 'updateOutputInfo' => $updateOutputInfo, 'updateErrorsInfo' => $updateErrorsInfo]);
    }

    public function actionDicomViewer(): string
    {
        $this->layout = 'empty';
        return $this->render('dicom-viewer');
    }

    public function actionPrivacyPolicy(): string
    {
        return $this->renderPartial('privacy-policy');
    }

    public function actionPatient($accessToken = null): string
    {
        if ($accessToken !== null) {
            $patient = User::findIdentityByAccessToken($accessToken);
        } else {
            $patient = Yii::$app->user->identity;
        }
        if ($patient !== null) {
            if($patient->status === 2){
                return $this->render('patient-expired', ['patient' => $patient]);
            }
            return $this->render('patient', ['patient' => $patient]);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @throws \Exception
     */
    public function actionPatientSearch(): string
    {
        $this->layout = 'administrate';
        $model = new PatientSearch();
        $results = null;
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            if ($model->validate()) {
                $results = $model->search();
            }
        }
        return $this->render('patient-search', ['model' => $model, 'results' => $results]);
    }

    public function actionDicomHint()
    {
        echo 'В разработке';
    }

    public function actionLogin(): Response|string
    {
        if (Yii::$app->user->isGuest) {
            $model = new LoginForm(['scenario' => LoginForm::SCENARIO_NEW_LOGIN]);
            return $this->render('new-login', ['model' => $model]);
        }
        if (Yii::$app->user->can("manage")) {
            return $this->redirect(Url::toRoute('/site/personal-control'));
        }
        return $this->redirect(Url::toRoute("/site/patient"));
    }
}
