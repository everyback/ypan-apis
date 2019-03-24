<?php

namespace App\Http\Controllers\File;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Model\FileModel as File;
use MongoDB;
use MongoDB\BSON;
use App\Model\UserFileModel as UserFile;
use DB;
use Psy\Exception;
use App\Model\FolderModel as Folder;
use MongoGrid\MongoGrid as Grid;
use zip\zip;
use SendFile\SendFile;
use App\Model\FileDownloadPathModel as DownloadPath;

class FileManagerController extends Controller
{
    //
    function move(Request $request)
    {

    }

    function copy(Request $request)
    {

    }





}
