<?php
class Rituals extends ApiController
{


    public function __construct()
    {
        $this->model = $this->model('Ritual');
    }

    /**
     * get all project details by id
     *@param integar $id
     *
     * @return response
     */
    public function list($project_id = 1396)
    {
        $project_id = $this->required('project_id');
        $rites =  $this->model->getRitesByProjectId($project_id);
        if ($rites != null) {
            $this->response($rites);
        } else {
            $this->error('Not found');
        }
    }

    /**
     * get all project details by id
     *@param integar $id
     *
     * @return response
     */
    public function index()
    {
        if ($response = $this->model->getSettings('app')) {
            //change image gallery to json object
            $response->value = json_decode($response->value);
            $response->value->intro = MEDIAURL . '/' . $response->value->intro;
            $this->response($response->value);
        } else {
            $this->error('Not found');
        }
    }

    /**
     * start new Rituals  
     *
     * @param  mixed $projectId
     * @param  mixed $substituteId
     * @return object
     */
    public function start()
    {
        $data = $this->requiredArray(['project_id', 'substitute_id', 'order_id']);
        // get rites of project 
        $data['project'] = $this->model->getProject($data['project_id']);
        $data['rites'] = $this->model->getRitesByProjectId($data['project_id']);
        if (!$data['rites']) {
            $this->error('Not found');
        }
        // store rites in order with  status 0
        $rituals = [];
        foreach ($data['rites'] as $rite) {
            $rituals[] = $this->model->storeRituals($rite, $data);
        }
        $rituals = $this->model->getRitualsByOrder($data['order_id']);
        // $this->response($rituals);
        $data['rituals'] =  $rituals;
        return $data;
    }

    /**
     * List Rituals
     * @param  mixed $order_id
     * @return object
     */
    public function orders()
    {
        $order_id = $this->required('order_id');
        // get list of Rituals by order_id
        if ($order_id != null) {
            $rituals = $this->model->getRitualsByOrder($order_id);
            $data = [
                'project_name' => $rituals[0]->project_name,
                'rituals' => $rituals
            ];
            $this->response($data);
        } else {
            $this->error('Not found');
        }
    }

    /**
     * update Rituals
     *
     * @param  mixed $ritual_id
     * @return object
     */
    public function update()
    {

        $ritual_id = $this->required('ritual_id');
        // get order Rituals
        $ritual =  $this->model->getRitualById($ritual_id);
        if ($ritual == null) {
            $this->error('Not Available');
        }
        if (exist(@$_POST['complete'])) {
            // check if start first
            if ($ritual->start  == 0) {
                $this->error('You must start first');
            }
            if ($ritual->complete  == 1) {
                $this->error('Already Completed');
            }
            if ($ritual->proof == 1 ) {
                $this->error("يجب رفع الفيديو");
            }

            $taken_time = $this->model->getTimeTaken($ritual->rite_id)->time_taken;
            if ((time() - $ritual->start_time)   < $taken_time * 60) {
                $this->error("لقد استغرقت وقتاً قصيرا");
            }
            // check if need to proof
            
            // if ($ritual->proof == 1) {
            //     if (@$_FILES['proof'] == null) $this->error('يجب رفع الفيديو');
            //     $video = uploadImage('proof', APPROOT . '/media/files/badal/', 25000000, false);
            //     if ($video["error"] != []) {
            //         $this->error("file cant upload");
            //     }
            //     $path = '/media/files/badal/' . $video['filename'];
            //     $this->model->updateProofRituals($ritual_id, $path);
            // }
            $rituals = $this->model->updateCompleteRituals($ritual_id);
        } elseif (exist(@$_POST['start'])) {
            if ($ritual->start  == 1) {
                $this->error('Already Started');
            }
            // check exist start rital and dont be complete
            $uncompletedRitual =  $this->model->getuncompleterdRitual($ritual->order_id);
            //    if($uncompletedRitual)$this->error('you must complete the ritual you started');
            if ($uncompletedRitual) $this->error('يجب أن تكمل المناسك التي بدأتها');
            $ritualsOrder = $this->model->getOrderRites($ritual->order_id, $ritual_id);
            if ($ritualsOrder != null) {
                $this->error(' يجب ان تبدا منسك ' . @$ritualsOrder[0]->title . ' اولا ');
            }
            $rituals = $this->model->updateStartRituals($ritual_id);
        } else {
            $this->error('No action');
        }
        $this->response("Data has been updated successfully");
    }
    ///EOT

    /**
     * List Rituals
     *
     * @param  mixed $order_id
     * @return object
     */
    public function uploadVideo()
    {
        $ritual_id = $this->required('ritual_id', 'proof');
        // get order Rituals
        $ritual =  $this->model->getRitualById($ritual_id);
        // check if need to proof
        if ($ritual->proof == "0") {  $this->error("لا يجب رفع الفيديو");  }
        elseif ($ritual->proof == "1") {
            if (@$_FILES['proof'] == null) $this->error('يجب رفع الفيديو');
            $video = uploadImage('proof', APPROOT . '/media/files/badal/', 25000000, false);
            if ($video["error"] != []) {
                $this->error("file cant upload");
            }
            $path = '/media/files/badal/' . $video['filename'];
            $this->model->uploadVideo($ritual_id, $path);
            $this->response("تم رفع الفيديو بنجاح");
        }
        else{
            if (@$_FILES['proof'] == null) $this->error('يجب رفع الفيديو');
            @unlink(APPROOT . $ritual->proof);
            $video = uploadImage('proof', APPROOT . '/media/files/badal/', 25000000, false);
            if ($video["error"] != []) { $this->error("file cant upload");  }
            $path = '/media/files/badal/' . $video['filename'];
            $this->model->uploadVideo($ritual_id, $path);
            $this->response("تم رفع الفيديو بنجاح");
        }
        $this->response("لا يوجد اثبات");
    }



    /**
     * List Rituals
     *
     * @param  mixed $order_id
     * @return object
     */
    public function saveVideo()
    {
        $data = $this->requiredArray(['ritual_id', 'proof']);
        // get order Rituals
        $ritual =  $this->model->getRitualById($data['ritual_id']);
        if ($ritual->proof == "0") {  $this->error("لا يجب رفع الفيديو");  }

        // check if need to proof
        $this->model->uploadVideo($data['ritual_id'], $data['proof']);
        $this->response("تم رفع الفيديو بنجاح");
        
    }
}
