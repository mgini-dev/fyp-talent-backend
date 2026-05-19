<?php

namespace App\Services;

use App\Models\User;
use App\Models\Talent;
use App\Models\Project;
use App\Models\Connection;
use App\Models\Post;

class DashboardService
{
    public function getDashboardStatsForUser(User $user)
    {
        if ($user->hasRole('Admin')) {
            return $this->getAdminStats();
        }

        if ($user->hasRole('Mentor')) {
            return $this->getMentorStats($user);
        }

        return $this->getStudentStats($user);
    }

    private function getAdminStats()
    {
        $totalUsers = User::count();
        $totalMentors = User::whereHas('roles', fn($q) => $q->where('name', 'Mentor'))->count();
        $totalStudents = User::whereHas('roles', fn($q) => $q->where('name', 'Student'))->count();
        
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        
        $totalConnections = Connection::where('status', 'accepted')->count();
        $totalTalents = Talent::count();

        // Recent Activity: Last 5 users joined
        $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get()->map(function($u) {
            return [
                'id' => $u->id,
                'user' => $u->first_name . ' ' . $u->last_name,
                'avatar' => $u->profile_photo_url,
                'action' => 'Joined the platform',
                'time' => $u->created_at->diffForHumans()
            ];
        });

        // Recent Projects: Last 5 projects created
        $recentProjects = Project::with(['student', 'mentor'])->orderBy('created_at', 'desc')->take(5)->get()->map(function($p) {
            return [
                'id' => $p->id,
                'user' => $p->student ? $p->student->first_name . ' ' . $p->student->last_name : 'Unknown',
                'avatar' => $p->student ? $p->student->profile_photo_url : null,
                'action' => 'Created project: ' . $p->title,
                'time' => $p->created_at->diffForHumans()
            ];
        });

        // Combine and sort
        $activity = collect($recentUsers)->merge($recentProjects)->sortByDesc('time')->take(8)->values()->all();

        return [
            'role' => 'admin',
            'data' => [
                'stats' => [
                    'total_users' => $totalUsers,
                    'total_mentors' => $totalMentors,
                    'total_students' => $totalStudents,
                    'active_projects' => $activeProjects,
                    'total_projects' => $totalProjects,
                    'total_connections' => $totalConnections,
                    'total_talents' => $totalTalents,
                ],
                'recent_activity' => $activity
            ]
        ];
    }

    private function getMentorStats(User $user)
    {
        $activeStudentsCount = Connection::where(function($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->where('status', 'accepted')
            ->where('type', 'mentor_student')
            ->count();

        $pendingRequestsCount = Connection::where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $mentoredProjectsCount = Project::where('mentor_id', $user->id)
            ->count();

        $totalPostsCount = Post::where('user_id', $user->id)->count();

        $recentRequests = Connection::with('requester')
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($c) {
                return [
                    'id' => 'conn_'.$c->id,
                    'user' => $c->requester ? $c->requester->first_name . ' ' . $c->requester->last_name : 'Someone',
                    'avatar' => $c->requester ? $c->requester->profile_photo_url : null,
                    'action' => 'Sent you a connection request',
                    'time' => $c->created_at->diffForHumans(),
                    'raw_time' => $c->created_at
                ];
            });

        $recentUpdates = Project::with('student')
            ->where('mentor_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => 'proj_'.$p->id,
                    'user' => $p->student ? $p->student->first_name . ' ' . $p->student->last_name : 'Unknown',
                    'avatar' => $p->student ? $p->student->profile_photo_url : null,
                    'action' => 'Updated project: ' . $p->title,
                    'time' => $p->updated_at->diffForHumans(),
                    'raw_time' => $p->updated_at
                ];
            });

        $activity = collect($recentRequests)->merge($recentUpdates)->sortByDesc('raw_time')->take(8)->values()->all();

        return [
            'role' => 'mentor',
            'data' => [
                'stats' => [
                    'active_students' => $activeStudentsCount,
                    'pending_requests' => $pendingRequestsCount,
                    'mentored_projects' => $mentoredProjectsCount,
                    'total_posts' => $totalPostsCount,
                ],
                'recent_activity' => $activity
            ]
        ];
    }

    private function getStudentStats(User $user)
    {
        $connectedMentors = Connection::where(function($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->where('status', 'accepted')
            ->where('type', 'mentor_student')
            ->count();

        $peerConnections = Connection::where(function($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->where('status', 'accepted')
            ->where('type', 'student_student')
            ->count();

        $activeProjects = Project::where('student_id', $user->id)->where('status', 'active')->count();
        $totalProjects = Project::where('student_id', $user->id)->count();

        $pendingSent = Connection::where('requester_id', $user->id)->where('status', 'pending')->count();

        // Recent Activity: Recent posts from connections
        $connections = Connection::where(function($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get();
            
        $connectedIds = $connections->map(function($c) use ($user) {
            return $c->requester_id === $user->id ? $c->receiver_id : $c->requester_id;
        })->unique();

        $recentPosts = Post::with('author')
            ->whereIn('user_id', $connectedIds)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => 'post_'.$p->id,
                    'user' => $p->author ? $p->author->first_name . ' ' . $p->author->last_name : 'Unknown',
                    'avatar' => $p->author ? $p->author->profile_photo_url : null,
                    'action' => 'Published a ' . $p->type,
                    'time' => $p->created_at->diffForHumans(),
                    'raw_time' => $p->created_at
                ];
            });

        $recentProjects = Project::where('student_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => 'proj_'.$p->id,
                    'user' => 'You',
                    'avatar' => null,
                    'action' => 'Updated project: ' . $p->title,
                    'time' => $p->updated_at->diffForHumans(),
                    'raw_time' => $p->updated_at
                ];
            });

        $activity = collect($recentPosts)->merge($recentProjects)->sortByDesc('raw_time')->take(8)->values()->all();

        return [
            'role' => 'student',
            'data' => [
                'stats' => [
                    'connected_mentors' => $connectedMentors,
                    'peer_connections' => $peerConnections,
                    'active_projects' => $activeProjects,
                    'total_projects' => $totalProjects,
                    'pending_sent' => $pendingSent,
                ],
                'recent_activity' => $activity
            ]
        ];
    }
}
