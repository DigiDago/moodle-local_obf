define(['jquery'], function($) {
    return {
        init: function() {
            $(".badgelist li:not(:nth-child(-n+10))").hide();
            // Hide all badges initially.

            $(".badgelist").each(function() {
                var $badgeList = $(this);
                var badgesPerPage = 10;
                var totalBadges = $badgeList.find("li").length;

                if (totalBadges > badgesPerPage) {
                    $badgeList.next(".show-more-button").show();
                    // Show the button only if there are more than 10 badges.

                    $badgeList.next(".show-more-button").click(function() {
                        var visibleBadges = $badgeList.find("li:visible").length + badgesPerPage;

                        $badgeList.find("li:lt(" + visibleBadges + ")").show();
                        // Show additional badges.

                        // If all badges are shown, hide the "Show More" button.
                        if (visibleBadges >= totalBadges) {
                            $(this).hide();
                        }
                    });
                }
            });
        }
    };
});
