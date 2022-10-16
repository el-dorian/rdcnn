<?php


namespace app\controllers;


use app\models\database\Archive_complex_execution_info;
use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use app\models\FileUtils;
use app\models\handlers\AccessHandler;
use app\models\LoginForm;
use app\models\utils\DownloadHandler;
use app\models\utils\FilesHandler;
use app\models\Viber;
use app\priv\Info;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class AuthController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/error', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'login',
                        ],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'logout',
                        ],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionLogout(): Response
    {
        if (Yii::$app->request->isPost) {
            $cookies = Yii::$app->response->cookies;
            $cookies->remove('access_token');
            Yii::$app->user->logout();
            return $this->redirect('/', 301);
        }
        return $this->redirect('/', 301);
    }

    /**
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionLogin(): ?array
    {
        if (Yii::$app->request->isPost && Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new LoginForm(['scenario' => LoginForm::SCENARIO_NEW_LOGIN]);
            $model->load(Yii::$app->request->post());
            // загружаю личный кабинет пользователя
            return $model->newLogin();
        }
        throw new NotFoundHttpException();
    }
}