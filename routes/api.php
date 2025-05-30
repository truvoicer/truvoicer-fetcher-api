<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Backend\AdminController;
use App\Http\Controllers\Api\Backend\CategoryController;
use App\Http\Controllers\Api\Backend\Notification\NotificationController;
use App\Http\Controllers\Api\Backend\PermissionController;
use App\Http\Controllers\Api\Backend\Property\PropertyController;
use App\Http\Controllers\Api\Backend\Property\PropertyProfileController;
use App\Http\Controllers\Api\Backend\Provider\ProviderController;
use App\Http\Controllers\Api\Backend\Provider\ProviderPropertyController;
use App\Http\Controllers\Api\Backend\Provider\ProviderRateLimitController;
use App\Http\Controllers\Api\Backend\Services\ServiceRequestScheduleController;
use App\Http\Controllers\Api\Backend\Services\SrRateLimitController;
use App\Http\Controllers\Api\Backend\Tools\EntityController;
use App\Http\Controllers\Api\Backend\ValidationController;
use App\Http\Controllers\Api\Backend\SearchController;
use App\Http\Controllers\Api\Backend\Services\ServiceController;
use App\Http\Controllers\Api\Backend\Services\ServiceRequestConfigController;
use App\Http\Controllers\Api\Backend\Services\ServiceRequestController;
use App\Http\Controllers\Api\Backend\Services\ServiceRequestParameterController;
use App\Http\Controllers\Api\Backend\Services\ServiceRequestResponseKeyController;
use App\Http\Controllers\Api\Backend\Services\ServiceResponseKeyController;
use App\Http\Controllers\Api\Backend\Tools\FileSystemController;
use App\Http\Controllers\Api\Backend\Tools\ImportExportController;
use App\Http\Controllers\Api\Backend\Tools\UtilsController;
use App\Http\Controllers\Api\Backend\UserController;
use App\Http\Controllers\Api\Frontend\ListController;
use App\Http\Controllers\Api\Frontend\OperationsController;
use App\Models\Provider;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum', 'ability:api:app_user'])->group(function () {
    Route::prefix('front')->name('front.')->group(function () {
        Route::get('/service/{service:name}/providers', [ListController::class, 'getCategoryProviderList'])->name('category.providers.list');
        Route::get('/service/list', [ListController::class, 'frontendServiceList'])->name('service.list');
        Route::get('/service/response-key/list', [ListController::class, 'frontendServiceResponseKeyList'])->name('service.response-key.list');
        Route::prefix('operation')->name('operation.')->group(function () {
            Route::post('/search/{type}', [OperationsController::class, 'searchOperation'])->name('search');
        });
    });
});
Route::middleware(['auth:sanctum', 'ability:api:admin,api:superuser,api:super_admin,api:user,api:app_user'])->group(function () {
    Route::prefix('notification')->name('notification.')->group(function () {
        Route::get('/list', [NotificationController::class, 'index'])->name('list');
        Route::get('/read/count', [NotificationController::class, 'getReadCount'])->name('read.count');
        Route::get('/unread/count', [NotificationController::class, 'getUnreadCount'])->name('unread.count');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/mark-all-unread', [NotificationController::class, 'markAllAsUnread'])->name('mark-all-unread');
        Route::get('/{notification}', [NotificationController::class, 'edit'])->name('detail');
        Route::post('/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{notification}/mark-unread', [NotificationController::class, 'markAsUnread'])->name('mark-unread');
        Route::delete('/{notification}/delete', [NotificationController::class, 'destroy'])->name('delete');
        Route::delete('/delete-all', [NotificationController::class, 'deleteAll'])->name('delete-all');
    });
});
Route::middleware(['auth:sanctum', 'ability:api:admin,api:superuser,api:super_admin,api:user,api:app_user'])->group(function () {
    Route::prefix('backend')->name('backend.')->group(function () {
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/api-token/generate', [AuthController::class, 'newToken'])->name('token.generate');
            Route::prefix('account')->name('account.')->group(function () {
                Route::post('/details', [AuthController::class, 'getAccountDetails'])->name('details');
            });
            Route::prefix('token')->name('token.')->group(function () {
                Route::get('/validate', [AuthController::class, 'validateToken'])->name('validate');
                Route::get('/user', [AuthController::class, 'getSingleUserByApiToken'])->name('user');
            });
        });

        Route::prefix('session')->name('session.')->group(function () {
            Route::prefix('user')->name('user.')->group(function () {
                Route::get('/detail', [UserController::class, 'getSessionUserDetail'])->name('detail');
                Route::patch('/update', [UserController::class, 'updateSessionUser'])->name('update');
                Route::prefix('permission')->name('permission.')->group(function () {
                    Route::get('/entity/{entity}/{id}', [UserController::class, 'getUserEntityPermissionList'])->name('entity');
                });
                Route::get('/api-token', [UserController::class, 'getSessionUserApiToken'])->name('api-token.detail');
                Route::prefix('api-token')->name('api-token.')->group(function () {
                    Route::get('/list', [UserController::class, 'getSessionUserApiTokenList'])->name('list');
                    Route::get('/generate', [UserController::class, 'generateSessionUserApiToken'])->name('generate');
                    Route::delete('/delete', [UserController::class, 'deleteSessionUserApiToken'])->name('delete');
                });
            });
        });
    });
});
Route::middleware(['auth:sanctum', 'ability:api:admin,api:superuser,api:super_admin,api:user'])->group(function () {
    Route::prefix('backend')->name('backend.')->group(function () {
        Route::prefix('validation')->name('validation.')->group(function () {
            Route::get('/all', [ValidationController::class, 'validateAll'])->name('all');
        });
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('search')->name('search.')->group(function () {
                Route::get('/{query}', [SearchController::class, 'search'])->name('query');
            });
        });
        Route::get('/permission/entity/list', [UserController::class, 'getProtectedEntitiesList'])->name('permission.entities');
        Route::post('/entities', [EntityController::class, 'index'])->name('entities');

        Route::prefix('sr')->name('sr.')->group(function () {
            Route::post('/list', [ServiceRequestController::class, 'getServiceRequestList'])->name('list');
            Route::get('/type/list', [ServiceRequestController::class, 'getTypeList'])->name('type.list');
        });
        Route::prefix('category')->name('category.')->group(function () {
            Route::get('/list', [CategoryController::class, 'getCategories'])->name('list');
            Route::post('/create', [CategoryController::class, 'createCategory'])->name('create');
            Route::prefix('batch')->name('batch.')->group(function () {
                Route::delete('/delete', [CategoryController::class, 'deleteBatch'])->name('delete');
            });
            Route::get('/{category}', [CategoryController::class, 'getSingleCategory'])->name('detail');
            Route::patch('/{category}/update', [CategoryController::class, 'updateCategory'])->name('update');
            Route::delete('/{category}/delete', [CategoryController::class, 'deleteCategory'])->name('delete');
        });
        Route::prefix('property')->name('property.')->group(function () {
            Route::get('/profiles', [PropertyProfileController::class, 'index'])->name('index');
        });
        Route::prefix('provider')->name('provider.')->group(function () {
            Route::get('/list', [ProviderController::class, 'getProviderList'])->name('list')->can('viewAny', Provider::class);
            Route::post('/create', [ProviderController::class, 'createProvider'])->name('create')->can('create', Provider::class);
            Route::get('/{provider}', [ProviderController::class, 'getProvider'])->name('detail')->can('view', 'provider');
            Route::prefix('batch')->name('batch.')->group(function () {
                Route::delete('/delete', [ProviderController::class, 'deleteBatchProviders'])->name('delete');
            });
            Route::prefix('{provider}')->name('single.')->group(function () {
                Route::patch('/update', [ProviderController::class, 'updateProvider'])->name('update')->can('update', 'provider');
                Route::delete('/delete', [ProviderController::class, 'deleteProvider'])->name('delete')->can('delete', 'provider');
                Route::prefix('rate-limits')->name('rate-limits.')->group(function () {
                    Route::post('/create', [ProviderRateLimitController::class, 'createProviderRateLimit'])->name('create');
                    Route::get('/{providerRateLimit}', [ProviderRateLimitController::class, 'getProviderRateLimit'])->name('detail');
                    Route::delete('/{providerRateLimit}/delete', [ProviderRateLimitController::class, 'deleteProviderRateLimit'])->name('delete');
                    Route::patch('/{providerRateLimit}/update', [ProviderRateLimitController::class, 'updateProviderRateLimit'])->name('update');
                });
                Route::prefix('property')->name('property.')->group(function () {
                    Route::get('/list', [ProviderPropertyController::class, 'getProviderPropertyList'])->name('list');
                    Route::prefix('batch')->name('batch.')->group(function () {
                        Route::delete('/delete', [ProviderPropertyController::class, 'deleteBatch'])->name('delete');
                    });
                    Route::get('/{property}', [ProviderPropertyController::class, 'getProviderProperty'])->name('detail');
                    Route::patch('/{property}/update', [ProviderPropertyController::class, 'saveProviderProperty'])->name('save');
                    Route::delete('/{property}/delete', [ProviderPropertyController::class, 'deleteProviderProperty'])->name('delete');
                });
                Route::prefix('service-request')->name('service-request.')->group(function () {
                    Route::get('/list', [ServiceRequestController::class, 'getProviderServiceRequestList'])->name('list');
                    Route::get('/service/{service}', [ServiceRequestController::class, 'getProviderServiceRequest'])->name('service.detail');
                    Route::get('/test-run', [ServiceRequestController::class, 'runApiRequest'])->name('test-run');
                    Route::post('/response-keys/merge', [ServiceRequestController::class, 'mergeServiceRequestResponseKeys'])->name('response-keys.merge');
                    Route::post('/create', [ServiceRequestController::class, 'createServiceRequest'])->name('create');
                    Route::prefix('batch')->name('batch.')->group(function () {
                        Route::delete('/delete', [ServiceRequestController::class, 'deleteBatchServiceRequest'])->name('delete');
                    });
                    Route::get('/{serviceRequest}', [ServiceRequestController::class, 'getServiceRequest'])->name('detail');
                    Route::prefix('{serviceRequest}')->name('single.')->group(function () {
                        Route::prefix('child')->name('child.')->group(function () {
                            Route::get('/list', [ServiceRequestController::class, 'getChildServiceRequestList'])->name('list');
                            Route::post('/create', [ServiceRequestController::class, 'createChildServiceRequest'])->name('create');
                            Route::patch('/{childSr}/override', [ServiceRequestController::class, 'overrideChildServiceRequest'])->name('override');
                            Route::post('/{childSr}/duplicate', [ServiceRequestController::class, 'duplicateChildServiceRequest'])->name('duplicate');
                        });
                        Route::post('/populate-response-keys', [ServiceRequestController::class, 'populateSrResponseKeys'])->name('populate-response-keys');
                        Route::delete('/delete', [ServiceRequestController::class, 'deleteServiceRequest'])->name('delete');
                        Route::patch('/defaults/update', [ServiceRequestController::class, 'updateSrDefaults'])->name('defaults.update');
                        Route::patch('/update', [ServiceRequestController::class, 'updateServiceRequest'])->name('update');
                        Route::middleware('can:view,provider')->get('/request/run', [ServiceRequestController::class, 'runSrRequest'])
                            ->name('request.run');
                        Route::post('/duplicate', [ServiceRequestController::class, 'duplicateServiceRequest'])->name('duplicate');
                        Route::prefix('schedule')->name('schedule.')->group(function () {
                            Route::post('/create', [ServiceRequestScheduleController::class, 'createRequestSchedule'])->name('create');
                            Route::get('/{srSchedule}', [ServiceRequestScheduleController::class, 'getServiceSchedule'])->name('detail');
                            Route::delete('/{srSchedule}/delete', [ServiceRequestScheduleController::class, 'deleteRequestSchedule'])->name('delete');
                            Route::patch('/{srSchedule}/update', [ServiceRequestScheduleController::class, 'updateRequestSchedule'])->name('update');
                        });
                        Route::prefix('rate-limits')->name('rate-limits.')->group(function () {
                            Route::post('/create', [SrRateLimitController::class, 'createRequestRateLimit'])->name('create');
                            Route::get('/{srRateLimit}', [SrRateLimitController::class, 'getServiceRateLimit'])->name('detail');
                            Route::delete('/{srRateLimit}/delete', [SrRateLimitController::class, 'deleteRequestRateLimit'])->name('delete');
                            Route::patch('/{srRateLimit}/update', [SrRateLimitController::class, 'updateRequestRateLimit'])->name('update');
                        });
                        Route::prefix('config')->name('config.')->group(function () {
                            Route::prefix('property')->name('property.')->group(function () {
                                Route::get('/list', [ServiceRequestConfigController::class, 'getRequestConfigList'])->name('list');
                                Route::prefix('batch')->name('batch.')->group(function () {
                                    Route::delete('/delete', [ServiceRequestConfigController::class, 'deleteBatch'])->name('delete');
                                });
                                Route::get('/{property}', [ServiceRequestConfigController::class, 'getServiceRequestConfig'])->name('detail');
                                Route::patch('/{property}/update', [ServiceRequestConfigController::class, 'createRequestConfig'])->name('save');
                                Route::delete('/{property}/delete', [ServiceRequestConfigController::class, 'deleteRequestConfig'])->name('delete');
                            });

                        });
                        Route::prefix('parameter')->name('parameter.')->group(function () {
                            Route::get('/list', [ServiceRequestParameterController::class, 'getServiceRequestParameterList'])->name('list');
                            Route::get('/list/single', [ServiceRequestParameterController::class, 'getSingleServiceRequestParameters'])->name('list.single');
                            Route::post('/create', [ServiceRequestParameterController::class, 'createServiceRequestParameter'])->name('create');
                            Route::prefix('batch')->name('batch.')->group(function () {
                                Route::delete('/delete', [ServiceRequestParameterController::class, 'deleteBatch'])->name('delete');
                            });
                            Route::get('/{serviceRequestParameter}', [ServiceRequestParameterController::class, 'getServiceRequestParameter'])->name('detail');
                            Route::delete('/{serviceRequestParameter}/delete', [ServiceRequestParameterController::class, 'deleteServiceRequestParameter'])->name('delete');
                            Route::patch('/{serviceRequestParameter}/update', [ServiceRequestParameterController::class, 'updateServiceRequestParameter'])->name('update');
                        });
                        Route::prefix('response-key')->name('response-key.')->group(function () {
                            Route::get('/list', [ServiceRequestResponseKeyController::class, 'getRequestResponseKeyList'])->name('list');
                            Route::post('/create', [ServiceRequestResponseKeyController::class, 'createRequestResponseKey'])->name('create');

                            Route::prefix('batch')->name('batch.')->group(function () {
                                Route::delete('/delete', [ServiceRequestResponseKeyController::class, 'deleteBatch'])->name('delete');
                            });
                            Route::prefix('service')->name('service.')->group(function () {
                                Route::get('/{sResponseKey}', [ServiceRequestResponseKeyController::class, 'getRequestResponseKeyByResponseKey'])->name('detail');
                                Route::post('/{sResponseKey}/save', [ServiceRequestResponseKeyController::class, 'saveRequestResponseKey'])->name('save');
                            });

                            Route::get('/{srResponseKey}', [ServiceRequestResponseKeyController::class, 'getRequestResponseKey'])->name('detail');
                            Route::delete('/{srResponseKey}/delete', [ServiceRequestResponseKeyController::class, 'deleteRequestResponseKey'])->name('delete');
                            Route::patch('/{srResponseKey}/update', [ServiceRequestResponseKeyController::class, 'updateRequestResponseKey'])->name('update');
                        });
                    });
                });
            });
        })->scopeBindings();
        Route::prefix('service')->name('service.')->group(function () {
            Route::get('/list', [ServiceController::class, 'getServices'])->name('list');
            Route::post('/create', [ServiceController::class, 'createService'])->name('create');
            Route::prefix('batch')->name('batch.')->group(function () {
                Route::delete('/delete', [ServiceController::class, 'deleteBatch'])->name('delete');
            });
            Route::get('/{service:name}/providers', [ServiceController::class, 'getServiceProviders'])->name('detail.name.provider.list');
            Route::get('/{service}', [ServiceController::class, 'getService'])->name('detail');
            Route::prefix('{service}')->name('single.')->group(function () {
                Route::get('/provider/list', [ServiceController::class, 'getServiceProviders'])->name('provider.list');
                Route::patch('/update', [ServiceController::class, 'updateService'])->name('update');
                Route::delete('/delete', [ServiceController::class, 'deleteService'])->name('delete');
                Route::prefix('response-key')->name('response-key.')->group(function () {
                    Route::prefix('batch')->name('batch.')->group(function () {
                        Route::delete('/delete', [ServiceResponseKeyController::class, 'deleteBatch'])->name('delete');
                    });
                    Route::post('/load-default', [ServiceResponseKeyController::class, 'loadDefaultServiceResponseKeys'])->name('load-default');
                    Route::get('/list', [ServiceResponseKeyController::class, 'getServiceResponseKeyList'])->name('list');
                    Route::post('/create', [ServiceResponseKeyController::class, 'createServiceResponseKey'])->name('create');
                    Route::get('/{serviceResponseKey}', [ServiceResponseKeyController::class, 'getServiceResponseKey'])->name('detail');
                    Route::delete('/{serviceResponseKey}/delete', [ServiceResponseKeyController::class, 'deleteServiceResponseKey'])->name('delete');
                    Route::patch('/{serviceResponseKey}/update', [ServiceResponseKeyController::class, 'updateServiceResponseKey'])->name('update');
                });
            });
        });
        Route::prefix('tools')->name('tools.')->group(function () {
            Route::get('/export/list', [ImportExportController::class, 'getExportList'])->name('export.list');
            Route::post('/export', [ImportExportController::class, 'runExport'])->name('export');
            Route::prefix('/import')->name('import.')->group(function () {
                Route::post('/parse', [ImportExportController::class, 'parseImport'])->name('parse');
                Route::post('/mappings', [ImportExportController::class, 'runImport'])->name('mappings');
            });
            Route::prefix('utils')->name('utils.')->group(function () {
                Route::get('/variable/list', [UtilsController::class, 'getVariableList'])->name('variable.list');
                Route::get('/pagination-types', [UtilsController::class, 'getPaginationTypes'])->name('pagination-types');
            });
            Route::prefix('filesystem')->name('filesystem.')->group(function () {
                Route::get('/list', [FileSystemController::class, 'getFiles'])->name('list');
                Route::get('/{file}', [FileSystemController::class, 'getSingleFile'])->name('detail');
                Route::prefix('{file}')->name('single.')->group(function () {
                    Route::get('/download', [FileSystemController::class, 'downloadFile'])->name('download');
                    Route::post('/delete', [FileSystemController::class, 'deleteFile'])->name('delete');
                });
            });
        });
    });
});

Route::middleware(['auth:sanctum', 'ability:api:superuser,'])->group(function () {
    Route::prefix('backend')->name('backend.')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('user')->name('user.')->group(function () {
                Route::get('/role/list', [AdminController::class, 'getUserRoleList'])->name('role.list');
            });
        });

        Route::prefix('permission')->name('permission.')->group(function () {
            Route::get('/{permission}', [PermissionController::class, 'getSinglePermission'])->name('detail');
            Route::post('/{permission}/update', [PermissionController::class, 'updatePermission'])->name('update');
            Route::get('/list', [PermissionController::class, 'getPermissions'])->name('list');
            Route::post('/create', [PermissionController::class, 'createPermission'])->name('create');
            Route::post('/delete', [PermissionController::class, 'deletePermission'])->name('delete');

            Route::prefix('user')->name('user.')->group(function () {
                Route::get('/{user}/entity/list', [PermissionController::class, 'getProtectedEntitiesList'])->name('entity.list');
                Route::post('/{user}/entity/save', [PermissionController::class, 'saveUserEntityPermissions'])->name('entity.save');
                Route::get('/{user}/entity/{entity}/list', [PermissionController::class, 'getUserEntityPermissionList'])->name('single.entity.list');
                Route::get('/{user}/entity/{entity}/{id}', [PermissionController::class, 'getUserEntityPermission'])->name('single.entity.permission');
                Route::post('/{user}/entity/{entity}/{id}/delete', [PermissionController::class, 'deleteUserEntityPermissions'])->name('single.entity.permission.delete');
            });
        });
    });
});

Route::middleware(['auth:sanctum', 'ability:api:admin,api:superuser,api:super_admin'])->group(function () {
    Route::prefix('backend')->name('backend.')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('user')->name('user.')->group(function () {
                Route::get('/list', [AdminController::class, 'getUsersList'])->name('list');
                Route::post('/create', [AdminController::class, 'createUser'])->name('create');
                Route::prefix('batch')->name('batch.')->group(function () {
                    Route::delete('/delete', [AdminController::class, 'deleteBatchUser'])->name('delete');
                });
                Route::get('/{user}', [AdminController::class, 'getSingleUser'])->name('detail');
                Route::prefix('{user}')->name('single.')->group(function () {
                    Route::patch('/update', [AdminController::class, 'updateUser'])->name('update');
                    Route::delete('/delete', [AdminController::class, 'deleteUser'])->name('delete');
                    Route::prefix('api-token')->name('api-token.')->group(function () {
                        Route::get('/list', [AdminController::class, 'getUserApiTokens'])->name('list');
                        Route::post('/generate', [AdminController::class, 'generateNewApiToken'])->name('generate');
                        Route::post('/delete', [AdminController::class, 'deleteSessionUserApiToken'])->name('session.delete');
                        Route::get('/{personalAccessToken}', [AdminController::class, 'getApiToken'])->name('detail');
                        Route::patch('/{personalAccessToken}/update', [AdminController::class, 'updateApiTokenExpiry'])->name('update');
                        Route::delete('/{personalAccessToken}/delete', [AdminController::class, 'deleteApiToken'])->name('delete');
                    });
                });
            });
        });
        Route::prefix('property')->name('property.')->group(function () {
            Route::get('/list', [PropertyController::class, 'getPropertyList'])->name('list');
            Route::get('/{property}', [PropertyController::class, 'getProperty'])->name('detail');
        });
    });
});
