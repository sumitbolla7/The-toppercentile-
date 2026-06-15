<?php

namespace TTP_CRM\Database;

defined('ABSPATH') || exit;

class CampaignRepository
{
    public function get_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'ttp_crm_campaigns';
    }

    public function all()
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = "SELECT * FROM {$table} ORDER BY id DESC";

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function insert($data)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $now   = current_time('mysql');

        $result = $wpdb->insert(
            $table,
            array(
                'channel'      => $data['channel'],
                'title'        => $data['title'],
                'message'      => $data['message'],
                'tags_filter'  => $data['tags_filter'],
                'stage_filter' => $data['stage_filter'],
                'sent_count'   => 0,
                'status'       => 'draft',
                'created_at'   => $now,
                'sent_at'      => null,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        return false !== $result ? (int) $wpdb->insert_id : 0;
    }

    public function mark_sent($campaign_id, $sent_count)
    {
        global $wpdb;

        $table = $this->get_table_name();

        return false !== $wpdb->update(
            $table,
            array(
                'status'     => 'sent',
                'sent_count' => absint($sent_count),
                'sent_at'    => current_time('mysql'),
            ),
            array('id' => absint($campaign_id)),
            array('%s', '%d', '%s'),
            array('%d')
        );
    }

    public function count_campaigns()
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = "SELECT COUNT(*) FROM {$table}";

        return (int) $wpdb->get_var($sql);
    }
}
