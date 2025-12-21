<?php
class Projects extends ApiController
{

    public $BadalOrder;

    public function __construct()
    {
        $this->model = $this->model('Project');
        $this->BadalOrder = $this->model('Badalorder');
    }

    /**
     * get all project details by id
     *@param integar $id
     *
     * @return response
     */
    public function view()
    {
        $id = $this->required('id');
        #dd($this->model->getBy(['project_id'=>$id, 'status' => 1]));
        if ($response = $this->model->getBy(['project_id' => $id, 'status' => 1])) {
            //change image gallery to json object
            $galery = str_replace('&quot;', '', trim(trim($response->image, ']'), '['));
            $galery = str_replace('&#34;', '', trim(trim($galery, ']'), '['));
            $response->image = array_filter(explode(',', $galery), 'strlen');
            $response->donation_type = json_decode($response->donation_type);
            $this->response($response);
        } else {
            $this->error('Not found');
        }
    }

    /**
     * view list of projects by category Id
     * 
     * @param integar $category_id
     * @return response
     */
    public function list()
    {
        $id = $this->required('category_id');
        if ($response = $this->model->projectsByCategory($id)) {
            // $projects = [];
            // foreach ($response as $project) {
            //     $galery = str_replace('&quot;', '', trim(trim($project->image, ']'), '['));
            //     $galery = str_replace('&#34;', '', trim(trim($galery, ']'), '['));
            //     $project->image = array_filter(explode(',', $galery), 'strlen');

            //     $projects[] = $project;
            // }
            $this->response($response);
        } else {
            $this->error('Not found');
        }
    }

    /**
     * view list of categories 
     *
     * @return response
     */
    public function categories()
    {
        if ($response = $this->model->getAppCategories()) {
            $this->response($response);
        } else {
            $this->error('Not found');
        }
    }

    /**
     * view albadal projects
     * @return response
     */

    public function albadal()
    {
        $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
        $count = isset($_GET['count']) ? (int) $_GET['count'] : 20;

        if ($start < 0) $start = 0;
        if ($count < 1 || $count > 100) $count = 20;

        $setting = $this->BadalOrder->getSettingById(15);
        $setting = json_decode($setting->value);
        $types = [];
        if ($setting->haij_status) $types[] = "'hajj'";
        if ($setting->umrah_status) $types[] = "'umrah'";

        if ($types && $response = $this->model->getTableEdits($types, $start, $count)) {

            $total = $this->model->getTableEditsCount($types);

            $this->response([
                'status' => 'success',
                'data' => $response,
                'pagination' => [
                    'start' => $start,
                    'count' => $count,
                    'total' => $total,
                    'current_page' => floor($start / $count) + 1,
                    'total_pages' => ceil($total / $count),
                    'has_more' => ($start + $count) < $total
                ],
                
            ]);
        } else {
            $this->error('Not found');
        }
    }
    /**
     * get selected projects
     * @return response
     */

    public function selectedProjects()
    {
        $Badalsetting = @json_decode(@$this->model->getSettings('badal')->value)->badal_selected_projects;
        if ($Badalsetting && $response = $this->model->getSelectedProjects(implode(',', json_decode($Badalsetting)))) {
            $this->response($response);
        } else {
            $this->error('No Project');
        }
    }

    /**
     * get umrah projects
     * @return response
     */

    public function umrah()
    {
        if ($umrah = $this->model->getUmrah()) {
            // foreach ($umrah as $project) {
            //     $galery = str_replace('&quot;', '', trim(trim($project->image, ']'), '['));
            //     $galery = str_replace('&#34;', '', trim(trim($galery, ']'), '['));
            //     $galery = MEDIAURL . '/' . $galery;
            //     $galery = str_replace(',', ',' . MEDIAURL . '/', trim($galery));
            //     $project->image = array_filter(explode(',', $galery), 'strlen');
            //     $projects[] = $project;
            // }
            $this->response($umrah);
        } else {
            $this->error('Not found');
        }
    }

    /**
     * get hajj projects
     * @return response
     */

    public function hajj()
    {
        if ($hajj = $this->model->getHajj()) {
            // foreach ($hajj as $project) {
            //     $galery = str_replace('&quot;', '', trim(trim($project->image, ']'), '['));
            //     $galery = str_replace('&#34;', '', trim(trim($galery, ']'), '['));
            //     $galery = MEDIAURL . '/' . $galery;
            //     $galery = str_replace(',', ',' . MEDIAURL . '/', trim($galery));
            //     $project->image = array_filter(explode(',', $galery), 'strlen');
            //     $projects[] = $project;
            // }
            $this->response($hajj);
        } else {
            $this->error('Not found');
        }
    }


    /**
     * checkbadal
     * @return response
     */

    public function checkbadal()
    {
        $setting = $this->BadalOrder->getSettingById(15);
        if ($setting == null) {
            $this->error('No data');
        } else {
            $setting = json_decode($setting->value);
            if (!$setting->badalenabled) {
                $this->error('The Badal is not activated');
            }
            $response = [
                [
                    'id'        => 1,
                    'tag'       => 'haij',
                    'status'    => $setting->haij_status,
                    'text'      => $setting->haij_text,
                    'icon'      =>  URLROOT . "/media/files/badal/" . $setting->haij_icon,
                    'image'      =>  URLROOT . "/media/files/badal/" . $setting->haij_image,
                ],
                [
                    'id'        => 2,
                    'tag'       => 'umrah',
                    'status'    => $setting->umrah_status,
                    'text'      => $setting->umrah_text,
                    'icon'      =>  URLROOT . "/media/files/badal/" . $setting->umrah_icon,
                    'image'      =>  URLROOT . "/media/files/badal/" . $setting->umrah_image,
                ]


            ];
            $this->response($response);
        }
    }
}
