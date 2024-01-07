<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Backend\AdminController;
use App\Http\Controllers\Api\Backend\CategoryController;
use App\Http\Controllers\Api\Backend\PermissionController;
use App\Http\Controllers\Api\Backend\PropertyController;
use App\Http\Controllers\Api\Backend\Provider\ProviderController;
use App\Http\Controllers\Api\Backend\Provider\ProviderPropertyController;
use App\Http\Controllers\Api\Backend\RouteController;
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
use Illuminate\Http\Request;
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


Route::middleware(['auth:sanctum', 'ability:api:admin,api:superuser,api:user'])->group(function () {
    Route::prefix('route')->name('route.')->group(function () {
        Route::get('/list', [RouteController::class, 'getRouteList'])->name('list');
    });
});


Route::middleware(['auth:sanctum', 'ability:api:admin,api:superuser,api:user'])->group(function () {
    Route::prefix('front')->name('front.')->group(function () {
        Route::get('/category/{name}/providers', [ListController::class, 'getCategoryProviderList'])->name('category.providers.list');
        Route::get('/service/list', [ListController::class, 'frontendServiceList'])->name('service.list');
        Route::get('/service/response-key/list', [ListController::class, 'frontendServiceResponseKeyList'])->name('service.response-key.list');
        Route::prefix('operation')->name('operation.')->group(function () {
            Route::get('/{service_request_name}', [OperationsController::class, 'searchOperation'])->name('search');
        });
    });
    Route::prefix('backend')->name('backend.')->group(function () {
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::prefix('account')->name('account.')->group(function () {
                Route::post('/details', [AuthController::class, 'getAccountDetails'])->name('details');
                Route::post('/token/generate', [AuthController::class, 'newToken'])->name('token.generate');
            });
            Route::prefix('token')->name('token.')->group(function () {
                Route::get('/validate', [AuthController::class, 'validateToken'])->name('validate');
                Route::get('/user', [AuthController::class, 'getSingleUserByApiToken'])->name('user');
            });
        });
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('search')->name('search.')->group(function () {
                Route::get('/{query}', [SearchController::class, 'search'])->name('query');
            });
        });
        Route::get('/permission/entity/list', [UserController::class, 'getProtectedEntitiesList'])->name('entity.list');

        Route::prefix('session')->name('session.')->group(function () {
            Route::prefix('user')->name('user.')->group(function () {
                Route::get('/detail', [UserController::class, 'getSessionUserDetail'])->name('detail');
                Route::patch('/update', [UserController::class, 'updateSessionUser'])->name('update');
                Route::prefix('permission')->name('permission.')->group(function () {
                    Route::get('/entity/{entity}/{id}', [UserController::class, 'getUserEntityPermissionList'])->name('entity');
                });
                Route::get('/api-token', [UserController::class, 'getSessionUserApiToken'])->name('detail');
                Route::prefix('api-token')->name('api-token.')->group(function () {
                    Route::get('/list', [UserController::class, 'getSessionUserApiTokenList'])->name('list');
                    Route::get('/generate', [UserController::class, 'generateSessionUserApiToken'])->name('generate');
                    Route::delete('/delete', [UserController::class, 'deleteSessionUserApiToken'])->name('delete');
                });
            });
        });
        Route::prefix('category')->name('category.')->group(function () {
            Route::get('/list', [CategoryController::class, 'getCategories'])->name('list');
            Route::post('/create', [CategoryController::class, 'createCategory'])->name('create');
            Route::get('/{category}', [CategoryController::class, 'getSingleCategory'])->name('detail');
            Route::patch('/{category}/update', [CategoryController::class, 'updateCategory'])->name('update');
            Route::delete('/{category}/delete', [CategoryController::class, 'deleteCategory'])->name('delete');
        });
        Route::prefix('provider')->name('provider.')->group(function () {
            Route::get('/list', [ProviderController::class, 'getProviderList'])->name('list');
            Route::post('/create', [ProviderController::class, 'createProvider'])->name('create');
            Route::get('/{provider}', [ProviderController::class, 'getProvider'])->name('detail');
            Route::prefix('{provider}')->name('single.')->group(function () {
                Route::patch('/update', [ProviderController::class, 'updateProvider'])->name('update');
                Route::delete('/delete', [ProviderController::class, 'deleteProvider'])->name('delete');
                Route::prefix('property')->name('property.')->group(function () {
                    Route::get('/list', [ProviderPropertyController::class, 'getProviderPropertyList'])->name('list');
                    Route::get('/{property}', [ProviderPropertyController::class, 'getProviderProperty'])->name('detail');
                    Route::post('/{property}/save', [ProviderPropertyController::class, 'saveProviderProperty'])->name('save');
                    Route::delete('/{property}/delete', [ProviderPropertyController::class, 'deleteProviderProperty'])->name('delete');
                });
                Route::prefix('service-request')->name('service-request.')->group(function () {
                    Route::get('/list', [ServiceRequestController::class, 'getServiceRequestList'])->name('list');
                    Route::get('/service/{service}', [ServiceRequestController::class, 'getProviderServiceRequest'])->name('service.detail');
                    Route::get('/test-run', [ServiceRequestController::class, 'runApiRequest'])->name('test-run');
                    Route::post('/response-keys/merge', [ServiceRequestController::class, 'mergeServiceRequestResponseKeys'])->name('response-keys.merge');
                    Route::post('/create', [ServiceRequestController::class, 'createServiceRequest'])->name('create');
                    Route::get('/{serviceRequest}', [ServiceRequestController::class, 'getServiceRequest'])->name('detail');
                    Route::prefix('{serviceRequest}')->name('single.')->group(function () {
                        Route::delete('/delete', [ServiceRequestController::class, 'deleteServiceRequest'])->name('delete');
                        Route::patch('/update', [ServiceRequestController::class, 'updateServiceRequest'])->name('update');
                        Route::post('/duplicate', [ServiceRequestController::class, 'duplicateServiceRequest'])->name('duplicate');
                        Route::prefix('config')->name('config.')->group(function () {
                            Route::get('/list', [ServiceRequestConfigController::class, 'getRequestConfigList'])->name('list');
                            Route::post('/create', [ServiceRequestConfigController::class, 'createRequestConfig'])->name('create');
                            Route::get('/{serviceRequestConfig}', [ServiceRequestConfigController::class, 'getServiceRequestConfig'])->name('detail');
                            Route::delete('/{serviceRequestConfig}/delete', [ServiceRequestConfigController::class, 'deleteRequestConfig'])->name('delete');
                            Route::patch('/{serviceRequestConfig}/update', [ServiceRequestConfigController::class, 'updateRequestConfig'])->name('update');
                        });
                        Route::prefix('parameter')->name('parameter.')->group(function () {
                            Route::get('/list', [ServiceRequestParameterController::class, 'getServiceRequestParameterList'])->name('list');
                            Route::get('/list/single', [ServiceRequestParameterController::class, 'getSingleServiceRequestParameters'])->name('list.single');
                            Route::post('/create', [ServiceRequestParameterController::class, 'createServiceRequestParameter'])->name('create');
                            Route::get('/{serviceRequestParameter}', [ServiceRequestParameterController::class, 'getServiceRequestParameter'])->name('detail');
                            Route::delete('/{serviceRequestParameter}/delete', [ServiceRequestParameterController::class, 'deleteServiceRequestParameter'])->name('delete');
                            Route::patch('/{serviceRequestParameter}/update', [ServiceRequestParameterController::class, 'updateServiceRequestParameter'])->name('update');
                        });
                        Route::prefix('response-key')->name('response-key.')->group(function () {
                            Route::get('/list', [ServiceRequestResponseKeyController::class, 'getRequestResponseKeyList'])->name('list');
                            Route::post('/create', [ServiceRequestResponseKeyController::class, 'createRequestResponseKey'])->name('create');
                            Route::get('/{serviceResponseKey}', [ServiceRequestResponseKeyController::class, 'getRequestResponseKey'])->name('detail');
                            Route::delete('/{serviceResponseKey}/delete', [ServiceRequestResponseKeyController::class, 'deleteRequestResponseKey'])->name('delete');
                            Route::patch('/{serviceResponseKey}/update', [ServiceRequestResponseKeyController::class, 'updateRequestResponseKey'])->name('update');
                        });
                    });
                });
            });
        });
        Route::prefix('service')->name('service.')->group(function () {
            Route::get('/list', [ServiceController::class, 'getServices'])->name('list');
            Route::post('/create', [ServiceController::class, 'createService'])->name('create');
            Route::get('/{service}', [ServiceController::class, 'getService'])->name('detail');
            Route::prefix('{service}')->name('single.')->group(function () {
                Route::patch('/update', [ServiceController::class, 'updateService'])->name('update');
                Route::delete('/delete', [ServiceController::class, 'deleteService'])->name('delete');
                Route::prefix('response-key')->name('response-key.')->group(function () {
                    Route::get('/list', [ServiceResponseKeyController::class, 'getServiceResponseKeyList'])->name('list');
                    Route::post('/create', [ServiceResponseKeyController::class, 'createServiceResponseKey'])->name('create');
                    Route::get('/{serviceResponseKey}', [ServiceResponseKeyController::class, 'getServiceResponseKey'])->name('detail');
                    Route::delete('/{serviceResponseKey}/delete', [ServiceResponseKeyController::class, 'deleteServiceResponseKey'])->name('delete');
                    Route::patch('/{serviceResponseKey}/update', [ServiceResponseKeyController::class, 'updateServiceResponseKey'])->name('update');
                });
            });
        });
        Route::prefix('tools')->name('tools.')->group(function () {
            Route::get('/export/list', [ImportExportController::class, 'getExportList'])->name('list');
            Route::post('/export', [ImportExportController::class, 'runExport'])->name('export');
            Route::post('/import', [ImportExportController::class, 'runImport'])->name('import');
            Route::post('/import/mappings', [ImportExportController::class, 'runImportMappings'])->name('import.mappings');
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

Route::middleware(['auth:sanctum', 'ability:api:superuser'])->group(function () {
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
            Route::get('/provider/list', [PermissionController::class, 'getProviderList'])->name('provider.list');
            Route::get('/category/list', [PermissionController::class, 'getCategoryList'])->name('category.list');

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

Route::middleware(['auth:sanctum', 'ability:api:admin,api:superuser'])->group(function () {
    Route::prefix('backend')->name('backend.')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::prefix('user')->name('user.')->group(function () {
                Route::get('/list', [AdminController::class, 'getUsersList'])->name('list');
                Route::post('/create', [AdminController::class, 'createUser'])->name('create');
                Route::get('/{user}', [AdminController::class, 'getSingleUser'])->name('detail');
                Route::prefix('{user}')->name('single.')->group(function () {
                    Route::patch('/update', [AdminController::class, 'updateUser'])->name('update');
                    Route::delete('/delete', [AdminController::class, 'deleteUser'])->name('delete');
                    Route::prefix('api-token')->name('api-token.')->group(function () {
                        Route::get('/list', [AdminController::class, 'getUserApiTokens'])->name('list');
                        Route::post('/generate', [AdminController::class, 'generateNewApiToken'])->name('generate');
                        Route::post('/delete', [AdminController::class, 'deleteSessionUserApiToken'])->name('delete');
                        Route::get('/{personalAccessToken}', [AdminController::class, 'getApiToken'])->name('detail');
                        Route::patch('/{personalAccessToken}/update', [AdminController::class, 'updateApiTokenExpiry'])->name('update');
                        Route::delete('/{personalAccessToken}/delete', [AdminController::class, 'deleteApiToken'])->name('delete');
                    });
                });
            });
        });
        Route::prefix('property')->name('property.')->group(function () {
            Route::get('/list', [PropertyController::class, 'getPropertyList'])->name('list');
            Route::post('/create', [PropertyController::class, 'createProperty'])->name('create');
            Route::get('/{property}', [PropertyController::class, 'getProperty'])->name('detail');
            Route::patch('/{property}/update', [PropertyController::class, 'updateProperty'])->name('update');
            Route::delete('/{property}/delete', [PropertyController::class, 'deleteProperty'])->name('delete');
        });
    });
});
