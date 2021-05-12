<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\XssFixer\Controller\Admin;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Driver\Connection;

class ConfigController extends AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/xss_fixer/config", name="xss_fixer_admin_config")
     * @Template("@XssFixer/admin/config.twig")
     */
    public function index(Request $request, Connection $connection)
    {
        $dir = $this->eccubeConfig->get('eccube_theme_admin_dir').'/Order';
        $file = 'mail_confirm.twig';
        $path = $dir.'/'.$file;

        // ファイルが設置できれいれば成功とみなす。
        $success = file_exists($path);

        $success
            ? $this->addSuccess('修正ファイルは適用されています。', 'admin')
            : $this->addDanger('修正ファイルの適用に失敗しています。', 'admin');


        // scriptタグを網羅的に調べる
        foreach(['dtb_customer', 'dtb_order', 'dtb_shipping'] as $t) {
            $columns = $connection->getSchemaManager()->listTableColumns($t);
            $where = " ";
            foreach ($columns as $column) {

                $where .= $column->getName() . " LIKE '%<script>%' ";

                if ($column !== end($columns)) {
                    $where .= " OR ";
                }
            }

            $result = $connection->fetchAll('SELECT * FROM '.$t.' where '. $where);
            if (!empty($result)) {
                return [
                    'success' => $this->addDanger('攻撃された形跡があります。至急対応してください。 https://www.ec-cube.net/info/weakness/20210507/#check', 'admin'),
                ];
            }
        }

        return [
            'success' => $success,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/xss_fixer/config/download", name="xss_fixer_admin_config_download")
     */
    public function download()
    {
        $dir = $this->eccubeConfig->get('eccube_theme_admin_dir').'/Order';
        $file = 'mail_confirm.twig';
        $path = $dir.'/'.$file;

        $content = file_exists($path)
            ? file_get_contents($path)
            : '';

        $response = new Response();
        $filename = 'mail_confirm.twig';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
        $response->setContent($content);

        return $response;
    }
}
