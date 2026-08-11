<?php

namespace App\Enums;

enum NotificationType: string
{
    /** タスクが割り当てられた */
    case TASK_ASSIGNED = 'TASK_ASSIGNED';

    /** タスクが更新された */
    case TASK_UPDATED = 'TASK_UPDATED';

    /** タスクが完了した */
    case TASK_COMPLETED = 'TASK_COMPLETED';

    /** タスクアクションが追加された */
    case TASK_ACTION_ADDED = 'TASK_ACTION_ADDED';

    /** タスクが削除された */
    case TASK_DELETED = 'TASK_DELETED';
}
